<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\PenjualanItem;
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

            // VALIDASI SEBELUM RECORD DIBUAT
            // Validasi stok tersedia dan duplikat produk
            $this->validateBeforeCreate($this->itemsToCreate);

            unset($data['items_temp']);
        }

        // Note: Fitur draft telah dihapus, semua penyimpanan langsung final
        // Hapus status_dokumen jika ada (kolom ini akan dihapus dari database)
        unset($data['status_dokumen']);

        return $data;
    }

    /**
     * Validasi sebelum create: stok dan duplikat produk
     */
    protected function validateBeforeCreate(array $items): void
    {
        if (empty($items)) {
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
                throw ValidationException::withMessages([
                    'items_temp' => 'Produk pada baris '.($index + 1).' harus dipilih.',
                ]);
            }

            if ($qty < 1) {
                $productName = \App\Models\Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
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
        // Process items with FIFO allocation
        if (! empty($this->itemsToCreate)) {
            $this->createItemsWithFifo($this->itemsToCreate);
        }

        // Recalculate totals
        $this->record->recalculateTotals();

        // Send notification
        $user = Auth::user();
        if ($user) {
            Notification::make()
                ->title('Penjualan baru dibuat')
                ->body("No. Nota {$this->record->no_nota} berhasil disimpan.")
                ->icon('heroicon-o-check-circle')
                ->actions([
                    NotificationAction::make('Lihat')
                        ->url(PenjualanResource::getUrl('view', ['record' => $this->record])),
                ])
                ->sendToDatabase($user);
        }
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
            $this->getCreateFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check')
                ->formId('form'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
