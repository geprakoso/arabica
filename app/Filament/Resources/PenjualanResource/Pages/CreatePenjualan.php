<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use App\Services\ValidationLogger;
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

    protected string $saveMode = 'final'; // 'draft' or 'final'

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
        \Illuminate\Support\Facades\Log::info('mutateFormDataBeforeCreate', [
            'saveMode' => $this->saveMode,
            'has_items_temp' => isset($data['items_temp']),
            'items_temp_count' => isset($data['items_temp']) ? count($data['items_temp']) : 0,
        ]);

        // Extract items_temp for manual processing after record creation
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];

            // VALIDASI SEBELUM RECORD DIBUAT
            // Validasi stok tersedia dan duplikat produk
            // Hanya validasi stok untuk mode final, draft skip validasi stok
            if ($this->saveMode === 'final') {
                $this->validateBeforeCreate($this->itemsToCreate);
            }

            unset($data['items_temp']);
        }

        // Set status dokumen berdasarkan mode save
        $data['status_dokumen'] = $this->saveMode;

        return $data;
    }

    /**
     * Validasi sebelum create: stok dan duplikat produk
     */
    protected function validateBeforeCreate(array $items): void
    {
        // Start batch logging
        ValidationLogger::startBatch();

        if (empty($items)) {
            // Log validation error
            ValidationLogger::logMinimumItems(
                sourceType: 'Penjualan',
                sourceAction: 'create',
                minRequired: 1,
                currentCount: 0
            );

            throw ValidationException::withMessages([
                'items_temp' => 'Minimal harus ada 1 item produk.',
            ]);
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        // Cek duplikat produk - tidak boleh ada produk+batch+kondisi yang sama persis di baris berbeda
        $productKeys = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId > 0) {
                // Key unik: produk + batch + kondisi
                $key = $productId.'|'.($condition ?? '').'|'.$batchId;

                if (isset($productKeys[$key])) {
                    $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                    $batchInfo = $batchId > 0 ? ' (batch sama)' : '';
                    $conditionInfo = $condition ? " (kondisi: {$condition})" : '';
                    $errorMessage = "Produk '{$productName}'{$conditionInfo}{$batchInfo} sudah ada di baris {$productKeys[$key]}. Hapus duplikat di baris ".($index + 1).'. Jika stok tidak cukup, cukup tambahkan qty di baris yang sudah ada.';

                    // Log validation error
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

                    // Kirim notifikasi toast dan database
                    Notification::make()
                        ->title('Validasi Gagal - Duplikat Produk')
                        ->body($errorMessage)
                        ->icon('heroicon-o-exclamation-triangle')
                        ->danger()
                        ->persistent()
                        ->send();

                    $user = Auth::user();
                    if ($user) {
                        Notification::make()
                            ->title('Validasi Gagal - Duplikat Produk')
                            ->body($errorMessage)
                            ->icon('heroicon-o-exclamation-triangle')
                            ->danger()
                            ->sendToDatabase($user);
                    }

                    throw ValidationException::withMessages([
                        'items_temp' => $errorMessage,
                    ]);
                }
                $productKeys[$key] = $index + 1;
            }
        }

        // Aggregate total qty per produk (dengan mempertimbangkan batch dan kondisi)
        $totalQtyMap = [];
        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1) {
                // Log validation error
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

                // Log validation error
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

            // Buat key unik berdasarkan produk + batch + kondisi
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

        // Validasi setiap grup terhadap stok database
        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $condition = $group['condition'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];
            $rows = $group['rows'];

            $query = PembelianItem::query()
                ->where($productColumn, $productId)
                ->where($qtyColumn, '>', 0);

            if ($batchId > 0) {
                $query->whereKey($batchId);
            }

            if ($condition) {
                $query->where('kondisi', $condition);
            }

            $availableQty = (int) $query->sum($qtyColumn);

            if ($availableQty < $requestedQty) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                $rowInfo = count($rows) > 1 ? ' (baris: '.implode(', ', $rows).')' : '';
                $errorMessage = "Stok tidak cukup untuk {$productName}{$rowInfo}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}";

                // Log validation error
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

                // Kirim notifikasi toast dan database
                Notification::make()
                    ->title('Validasi Gagal - Stok Tidak Cukup')
                    ->body($errorMessage)
                    ->icon('heroicon-o-exclamation-triangle')
                    ->danger()
                    ->persistent()
                    ->send();

                $user = Auth::user();
                if ($user) {
                    Notification::make()
                        ->title('Validasi Gagal - Stok Tidak Cukup')
                        ->body($errorMessage)
                        ->icon('heroicon-o-exclamation-triangle')
                        ->danger()
                        ->sendToDatabase($user);
                }

                throw ValidationException::withMessages([
                    'items_temp' => $errorMessage,
                ]);
            }
        }
    }

    protected function afterCreate(): void
    {
        \Illuminate\Support\Facades\Log::info('afterCreate', [
            'saveMode' => $this->saveMode,
            'itemsToCreate_count' => count($this->itemsToCreate),
            'penjualan_id' => $this->record->getKey(),
        ]);

        // Process items - untuk final dan draft
        if (! empty($this->itemsToCreate)) {
            if ($this->saveMode === 'final') {
                // Final: Create items dengan FIFO (stok berkurang)
                $this->createItemsWithFifo($this->itemsToCreate);
            } else {
                // Draft: Create items tanpa FIFO (stok TIDAK berkurang)
                $this->createItemsWithoutStockDeduction($this->itemsToCreate);
            }

            // Recalculate totals
            $this->record->recalculateTotals();
        }

        // Send notification
        $user = Auth::user();
        if ($user) {
            $title = $this->saveMode === 'draft' ? 'Draft penjualan disimpan' : 'Penjualan baru dibuat';
            $body = $this->saveMode === 'draft'
                ? "Draft {$this->record->no_nota} berhasil disimpan. Belum mengurangi stok."
                : "No. Nota {$this->record->no_nota} berhasil disimpan.";

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-check-circle')
                ->actions([
                    NotificationAction::make('Lihat')
                        ->url(PenjualanResource::getUrl('view', ['record' => $this->record])),
                ])
                ->sendToDatabase($user);
        }
    }

    /**
     * Create items without stock deduction (for draft mode)
     */
    protected function createItemsWithoutStockDeduction(array $items): void
    {
        \Illuminate\Support\Facades\Log::info('createItemsWithoutStockDeduction started', [
            'items_count' => count($items),
            'penjualan_id' => $this->record->getKey(),
            'status_dokumen' => $this->record->status_dokumen,
        ]);

        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $customPrice = $item['harga_jual'] ?? null;
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            \Illuminate\Support\Facades\Log::info('Processing item', [
                'index' => $index,
                'productId' => $productId,
                'qty' => $qty,
            ]);

            if ($productId < 1 || $qty < 1) {
                \Illuminate\Support\Facades\Log::warning('Skipping item - invalid product or qty', [
                    'productId' => $productId,
                    'qty' => $qty,
                ]);

                continue;
            }

            try {
                // Untuk draft, simpan dengan batch_id = null (akan dialokasikan saat finalisasi)
                // atau simpan batch_id yang dipilih user (untuk referensi)
                $createdItem = PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $batchId > 0 ? $batchId : null,
                    'qty' => $qty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $condition,
                    'serials' => ! empty($serials) ? $serials : null,
                ]);

                \Illuminate\Support\Facades\Log::info('Item created', [
                    'item_id' => $createdItem->id_penjualan_item,
                    'productId' => $productId,
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to create item', [
                    'error' => $e->getMessage(),
                    'productId' => $productId,
                ]);
                throw $e;
            }
        }

        \Illuminate\Support\Facades\Log::info('createItemsWithoutStockDeduction completed');
    }

    /**
     * Validasi global: memeriksa total qty per produk/batch/kondisi
     * terhadap stok yang tersedia di database.
     */
    protected function validateTotalQtyAvailability(array $items): void
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        // Aggregate total qty per produk (dengan mempertimbangkan batch dan kondisi)
        $totalQtyMap = [];
        foreach ($items as $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            // Buat key unik berdasarkan produk + batch + kondisi
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

        // Validasi setiap grup terhadap stok database
        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $condition = $group['condition'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];

            $query = PembelianItem::query()
                ->where($productColumn, $productId)
                ->where($qtyColumn, '>', 0);

            if ($batchId > 0) {
                $query->whereKey($batchId);
            }

            if ($condition) {
                $query->where('kondisi', $condition);
            }

            $availableQty = (int) $query->sum($qtyColumn);

            if ($availableQty < $requestedQty) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk {$productName}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}",
                ]);
            }
        }
    }

    /**
     * Create items using FIFO batch allocation.
     */
    protected function createItemsWithFifo(array $items): void
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        // Validasi global: cek total qty per produk/batch/kondisi sebelum memulai transaksi
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
                $batch = PembelianItem::query()
                    ->where($productColumn, $productId)
                    ->whereKey($batchId)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages([
                        'items_temp' => 'Batch pembelian tidak ditemukan.',
                    ]);
                }

                $available = (int) ($batch->{$qtyColumn} ?? 0);

                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items_temp' => "Stok batch tidak cukup. Tersedia: {$available}, Dibutuhkan: {$qty}",
                    ]);
                }

                $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $batch->id_pembelian_item,
                    'qty' => $qty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $batch->kondisi,
                    'serials' => empty($takeSerials) ? null : $takeSerials,
                ]);

                continue;
            }

            // Get available batches using FIFO (oldest first)
            $batchesQuery = PembelianItem::query()
                ->where($productColumn, $productId)
                ->where($qtyColumn, '>', 0)
                ->orderBy('id_pembelian_item')
                ->lockForUpdate();

            if ($condition) {
                $batchesQuery->where('kondisi', $condition);
            }

            $batches = $batchesQuery->get();
            $available = (int) $batches->sum(fn ($batch) => (int) ($batch->{$qtyColumn} ?? 0));

            if ($available < $qty) {
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk produk ini. Tersedia: {$available}, Dibutuhkan: {$qty}",
                ]);
            }

            $remaining = $qty;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

                if ($batchAvailable <= 0) {
                    continue;
                }

                $takeQty = min($remaining, $batchAvailable);

                // Split serials for this batch
                $takeSerials = [];
                if (! empty($serials)) {
                    $takeSerials = array_splice($serials, 0, $takeQty);
                }

                // Create PenjualanItem - model hooks will handle stock mutation
                PenjualanItem::create([
                    'id_penjualan' => $this->record->getKey(),
                    'id_produk' => $productId,
                    'id_pembelian_item' => $batch->id_pembelian_item,
                    'qty' => $takeQty,
                    'harga_jual' => $customPrice,
                    'kondisi' => $condition ?? $batch->kondisi,
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

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Simpan Final
            \Filament\Actions\Action::make('saveFinal')
                ->label('Simpan Final')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->action(function () {
                    $this->saveMode = 'final';
                    $this->create();
                })
                ->formId('form'),

            // Tombol Simpan Draft
            \Filament\Actions\Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-m-document')
                ->color('warning')
                ->outlined()
                ->action(function () {
                    $this->saveMode = 'draft';
                    $this->create();
                })
                ->formId('form'),

            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
