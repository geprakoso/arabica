<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Services\ValidationLogger;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditPenjualan extends EditRecord
{
    protected static string $resource = PenjualanResource::class;

    protected array $itemsToCreate = [];

    protected string $saveMode = 'final';

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform existing items to items_temp format for the form
        $data['items_temp'] = collect($this->record->items)
            ->map(fn ($item) => [
                'id_produk' => $item->id_produk,
                'id_pembelian_item' => $item->id_pembelian_item,
                'kondisi' => $item->kondisi,
                'qty' => $item->qty,
                'harga_jual' => $item->harga_jual,
                'serials' => $item->serials ?? [],
            ])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract items_temp for processing
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];

            // VALIDASI SEBELUM SAVE
            // Validasi stok tersedia dan duplikat produk
            $this->validateBeforeSave($this->itemsToCreate);

            unset($data['items_temp']);
        }

        // Hapus status_dokumen jika ada
        unset($data['status_dokumen']);

        return $data;
    }

    /**
     * Validasi sebelum save: stok dan duplikat produk
     */
    protected function validateBeforeSave(array $items): void
    {
        // Start batch logging
        ValidationLogger::startBatch();

        if (empty($items)) {
            // Log validation error
            ValidationLogger::logMinimumItems(
                sourceType: 'Penjualan',
                sourceAction: 'update',
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
                        sourceAction: 'update',
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
                    sourceAction: 'update',
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
                    sourceAction: 'update',
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
                    sourceAction: 'update',
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update penjualan record
            $record->update([
                'id_karyawan' => $data['id_karyawan'] ?? $record->id_karyawan,
                'id_member' => $data['id_member'] ?? $record->id_member,
                'tanggal_penjualan' => $data['tanggal_penjualan'] ?? $record->tanggal_penjualan,
                'catatan' => $data['catatan'] ?? $record->catatan,
                'diskon_total' => $data['diskon_total'] ?? $record->diskon_total ?? 0,
            ]);

            // Delete existing items and recreate
            $record->items()->delete();

            // Create items with FIFO batch allocation
            if (! empty($this->itemsToCreate)) {
                $this->createItemsWithFifo($this->itemsToCreate);
            }

            // Recalculate totals
            $record->recalculateTotals();
            $record->recalculatePaymentStatus();

            return $record;
        });
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
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $customPrice = isset($item['harga_jual']) ? (int) $item['harga_jual'] : null;
            $condition = $item['kondisi'] ?? null;
            $serials = $item['serials'] ?? [];

            if ($batchId > 0) {
                // Specific batch selected - use it directly
                $this->fulfillWithSpecificBatch($productId, $qty, $batchId, $customPrice, $condition, $serials);
            } else {
                // Auto-select using FIFO
                $this->fulfillWithFifo($productId, $qty, $customPrice, $condition, $serials);
            }
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
        foreach ($items as $index => $item) {
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
                throw ValidationException::withMessages([
                    'items_temp' => "Stok tidak cukup untuk {$productName}. Tersedia: {$availableQty}, Dibutuhkan: {$requestedQty}",
                ]);
            }
        }
    }

    /**
     * Fulfill order using a specific batch.
     */
    protected function fulfillWithSpecificBatch(int $productId, int $qty, int $batchId, ?int $customPrice, ?string $condition, array $serials): void
    {
        $batch = PembelianItem::find($batchId);

        if (! $batch) {
            throw ValidationException::withMessages([
                'items_temp' => 'Batch pembelian tidak ditemukan.',
            ]);
        }

        PenjualanItem::create([
            'id_penjualan' => $this->record->id_penjualan,
            'id_produk' => $productId,
            'id_pembelian_item' => $batchId,
            'qty' => $qty,
            'harga_jual' => $customPrice,
            'kondisi' => $condition ?? $batch->kondisi,
            'serials' => ! empty($serials) ? $serials : null,
        ]);
    }

    /**
     * Fulfill order using FIFO batch allocation.
     */
    protected function fulfillWithFifo(int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials): void
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $remaining = $qty;
        $serialsToAssign = $serials;

        $batches = PembelianItem::where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when($condition, fn ($q) => $q->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = (int) $batch->{$qtyColumn};
            if ($available <= 0) {
                continue;
            }

            $take = min($remaining, $available);

            // Assign serials for this batch
            $batchSerials = [];
            if (! empty($serialsToAssign)) {
                $batchSerials = array_splice($serialsToAssign, 0, $take);
            }

            PenjualanItem::create([
                'id_penjualan' => $this->record->id_penjualan,
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->id_pembelian_item,
                'qty' => $take,
                'harga_jual' => $customPrice,
                'kondisi' => $condition ?? $batch->kondisi,
                'serials' => ! empty($batchSerials) ? $batchSerials : null,
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'items_temp' => "Gagal mengalokasikan stok untuk produk #{$productId}. Sisa: {$remaining}",
            ]);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Penjualan berhasil diupdate.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check-circle')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->formId('form'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
