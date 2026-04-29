<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use App\Services\ValidationLogger;
use Filament\Actions\Action;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected static bool $canCreateAnother = false;

    protected array $itemsToCreate = [];

    public string $saveMode = 'draft';

    protected function getRedirectUrl(): string
    {
        return PenjualanResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penjualan berhasil disimpan.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract items_temp for manual processing after record creation
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];

            // VALIDASI SEBELUM RECORD DIBUAT (hanya jika ada items)
            if (! empty($this->itemsToCreate)) {
                $this->validateBeforeCreate($this->itemsToCreate);
            }

            unset($data['items_temp']);
        }

        // Validasi: minimal harus ada 1 item produk atau 1 jasa
        $hasItems = ! empty($this->itemsToCreate);
        $hasJasa = ! empty($data['jasaItems'] ?? []);
        if (! $hasItems && ! $hasJasa) {
            throw ValidationException::withMessages([
                'items_temp' => 'Minimal harus ada 1 item produk atau 1 jasa.',
            ]);
        }

        // Set status sesuai pilihan user
        $data['status_dokumen'] = $this->saveMode === 'final' ? 'final' : 'draft';

        return $data;
    }

    /**
     * Validasi sebelum create: stok dan duplikat produk
     */
    protected function validateBeforeCreate(array $items): void
    {
        ValidationLogger::startBatch();

        if (empty($items)) {
            return;
        }

        // Cek duplikat produk
        $productKeys = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId > 0) {
                $key = $productId.'|'.($condition ?? '').'|'.$batchId;

                if (isset($productKeys[$key])) {
                    $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                    $batchInfo = $batchId > 0 ? ' (batch sama)' : '';
                    $conditionInfo = $condition ? " (kondisi: {$condition})" : '';
                    $errorMessage = "Produk '{$productName}'{$conditionInfo}{$batchInfo} sudah ada di baris {$productKeys[$key]}. Hapus duplikat di baris ".($index + 1).'.';

                    ValidationLogger::logDuplicate(
                        sourceType: 'Penjualan',
                        sourceAction: 'create',
                        productName: $productName,
                        row: $index + 1,
                        inputData: [
                            'product_id' => $productId,
                            'batch_id' => $batchId,
                            'condition' => $condition,
                            'duplicate_row' => $productKeys[$key],
                            'current_row' => $index + 1,
                        ]
                    );

                    Notification::make()
                        ->title('Validasi Gagal - Duplikat Produk')
                        ->body($errorMessage)
                        ->icon('heroicon-o-exclamation-triangle')
                        ->danger()
                        ->persistent()
                        ->send();

                    throw ValidationException::withMessages([
                        'items_temp' => $errorMessage,
                    ]);
                }
                $productKeys[$key] = $index + 1;
            }
        }

        // Aggregate total qty per produk
        $totalQtyMap = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1) {
                ValidationLogger::logRequired(
                    sourceType: 'Penjualan',
                    sourceAction: 'create',
                    fieldName: 'id_produk',
                    fieldLabel: 'Produk',
                    inputData: ['row' => $index + 1]
                );

                throw ValidationException::withMessages([
                    'items_temp' => 'Produk pada baris '.($index + 1).' harus dipilih.',
                ]);
            }

            if ($qty < 1) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                ValidationLogger::logFormat(
                    sourceType: 'Penjualan',
                    sourceAction: 'create',
                    fieldName: 'qty',
                    message: "Qty untuk {$productName} minimal 1",
                    inputData: [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'qty' => $qty,
                        'row' => $index + 1,
                    ]
                );

                throw ValidationException::withMessages([
                    'items_temp' => "Qty untuk {$productName} minimal 1.",
                ]);
            }

            $key = $productId.'|'.($condition ?? '').'|'.$batchId;

            if (! isset($totalQtyMap[$key])) {
                $totalQtyMap[$key] = [
                    'product_id' => $productId,
                    'condition' => $condition,
                    'batch_id' => $batchId,
                    'qty' => 0,
                    'rows' => [],
                ];
            }
            $totalQtyMap[$key]['qty'] += $qty;
            $totalQtyMap[$key]['rows'][] = $index + 1;
        }

        // Validasi stok database (gunakan StockBatch)
        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $condition = $group['condition'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];
            $rows = $group['rows'];

            $query = \App\Models\StockBatch::query()
                ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
                    $q->where('id_produk', $productId);
                    if ($condition) {
                        $q->where('kondisi', $condition);
                    }
                })
                ->where('qty_available', '>', 0);

            if ($batchId > 0) {
                $query->where('pembelian_item_id', $batchId);
            }

            $availableQty = (int) $query->sum('qty_available');

            if ($availableQty < $requestedQty) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                $rowInfo = count($rows) > 1 ? ' (baris: '.implode(', ', $rows).')' : '';
                $errorMessage = "Stok tidak cukup untuk {$productName}{$rowInfo}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}";

                ValidationLogger::logStock(
                    sourceType: 'Penjualan',
                    sourceAction: 'create',
                    productName: $productName,
                    available: $availableQty,
                    requested: $requestedQty,
                    inputData: [
                        'product_id' => $productId,
                        'batch_id' => $batchId,
                        'condition' => $condition,
                        'rows' => $rows,
                    ]
                );

                Notification::make()
                    ->title('Validasi Gagal - Stok Tidak Cukup')
                    ->body($errorMessage)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->danger()
                    ->persistent()
                    ->send();

                throw ValidationException::withMessages([
                    'items_temp' => $errorMessage,
                ]);
            }
        }
    }

    protected function afterCreate(): void
    {
        // Process items with FIFO allocation
        if (! empty($this->itemsToCreate)) {
            $this->createItemsWithFifo($this->itemsToCreate);
        }

        // Recalculate totals
        $this->record->recalculateTotals();

        // Kalau mode final, post langsung
        if ($this->saveMode === 'final' && $this->record->canPost()) {
            $this->record->post();
        }

        // Send notification
        $user = Auth::user();
        if ($user) {
            $modeLabel = $this->saveMode === 'final' ? 'Final' : 'Draft';
            Notification::make()
                ->title("Penjualan {$modeLabel} berhasil dibuat")
                ->body("No. Nota {$this->record->no_nota} berhasil disimpan.")
                ->icon('heroicon-o-check-circle')
                ->actions([
                    NotificationAction::make('Lihat')
                        ->url(PenjualanResource::getUrl('view', ['record' => $this->record])),
                ])
                ->sendToDatabase($user);
        }
    }

    protected function validateTotalQtyAvailability(array $items): void
    {
        $totalQtyMap = [];
        foreach ($items as $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $key = $productId.'|'.($condition ?? '').'|'.$batchId;

            if (! isset($totalQtyMap[$key])) {
                $totalQtyMap[$key] = [
                    'product_id' => $productId,
                    'condition' => $condition,
                    'batch_id' => $batchId,
                    'qty' => 0,
                ];
            }
            $totalQtyMap[$key]['qty'] += $qty;
        }

        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $condition = $group['condition'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];

            $query = \App\Models\StockBatch::query()
                ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
                    $q->where('id_produk', $productId);
                    if ($condition) {
                        $q->where('kondisi', $condition);
                    }
                })
                ->where('qty_available', '>', 0);

            if ($batchId > 0) {
                $query->where('pembelian_item_id', $batchId);
            }

            $availableQty = (int) $query->sum('qty_available');

            if ($availableQty < $requestedQty) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk {$productName}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}",
                ]);
            }
        }
    }

    protected function createItemsWithFifo(array $items): void
    {
        $this->validateTotalQtyAvailability($items);

        foreach ($items as $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $customPrice = $item['harga_jual'] ?? null;
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            if ($batchId > 0) {
                $stockBatch = \App\Models\StockBatch::where('pembelian_item_id', $batchId)
                    ->whereHas('pembelianItem', function ($q) use ($productId) {
                        $q->where('id_produk', $productId);
                    })
                    ->first();

                if (! $stockBatch) {
                    throw ValidationException::withMessages([
                        'items_temp' => 'StockBatch tidak ditemukan.',
                    ]);
                }

                if ($stockBatch->qty_available < $qty) {
                    throw ValidationException::withMessages([
                        'items_temp' => "Stok batch tidak cukup. Tersedia: {$stockBatch->qty_available}, Dibutuhkan: {$qty}",
                    ]);
                }

                $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $stockBatch->pembelian_item_id,
                    'qty' => $qty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $stockBatch->pembelianItem->kondisi,
                    'serials' => empty($takeSerials) ? null : $takeSerials,
                ]);

                continue;
            }

            $batchesQuery = \App\Models\StockBatch::query()
                ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
                    $q->where('id_produk', $productId);
                    if ($condition) {
                        $q->where('kondisi', $condition);
                    }
                })
                ->where('qty_available', '>', 0)
                ->orderBy('id');

            $batches = $batchesQuery->get();
            $available = (int) $batches->sum('qty_available');

            if ($available < $qty) {
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk produk ini. Tersedia: {$available}, Dibutuhkan: {$qty}",
                ]);
            }

            $remaining = $qty;

            foreach ($batches as $stockBatch) {
                if ($remaining <= 0) {
                    break;
                }

                $batchAvailable = $stockBatch->qty_available;

                if ($batchAvailable <= 0) {
                    continue;
                }

                $takeQty = min($remaining, $batchAvailable);

                $takeSerials = [];
                if (! empty($serials)) {
                    $takeSerials = array_splice($serials, 0, $takeQty);
                }

                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $stockBatch->pembelian_item_id,
                    'qty' => $takeQty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $condition ?? $stockBatch->pembelianItem->kondisi,
                    'serials' => empty($takeSerials) ? null : $takeSerials,
                ]);

                $remaining -= $takeQty;
            }
        }
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            return parent::handleRecordCreation($data);
        });
    }

    public function saveDraft(): void
    {
        $this->saveMode = 'draft';
        $this->create();
    }

    public function saveFinal(): void
    {
        $this->saveMode = 'final';
        $this->create();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->action('saveDraft'),
            Action::make('saveFinal')
                ->label('Simpan Final')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('saveFinal'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->color('danger'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
