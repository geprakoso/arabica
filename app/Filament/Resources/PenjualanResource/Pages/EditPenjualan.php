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

    protected string $saveMode = 'draft'; // 'draft' or 'final'

    protected bool $isFinalizing = false;

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
        // Jika status final, tidak bisa edit
        if ($this->record->isFinal()) {
            throw ValidationException::withMessages([
                'global' => 'Transaksi final tidak dapat diubah. Gunakan "Ubah ke Draft" terlebih dahulu.',
            ]);
        }

        // Extract items_temp for processing
        if (isset($data['items_temp']) && is_array($data['items_temp'])) {
            $this->itemsToCreate = $data['items_temp'];

            // VALIDASI SEBELUM SAVE
            // Validasi stok tersedia dan duplikat produk
            $this->validateBeforeSave($this->itemsToCreate);

            unset($data['items_temp']);
        }

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

            // Create items - bedakan antara update draft dan finalisasi
            if (! empty($this->itemsToCreate)) {
                if ($this->isFinalizing) {
                    // Finalisasi: Create items dengan FIFO (stok berkurang)
                    $this->createItemsWithFifo($this->itemsToCreate);
                } else {
                    // Update draft: Create items tanpa FIFO (stok TIDAK berkurang)
                    $this->createItemsWithoutStockDeduction($this->itemsToCreate);
                }
            }

            // Recalculate totals
            $record->recalculateTotals();
            $record->recalculatePaymentStatus();

            return $record;
        });
    }

    /**
     * Create items without stock deduction (for draft mode)
     */
    protected function createItemsWithoutStockDeduction(array $items): void
    {
        foreach ($items as $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $customPrice = isset($item['harga_jual']) ? (int) $item['harga_jual'] : null;
            $condition = $item['kondisi'] ?? null;
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);
            $serials = $item['serials'] ?? [];

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            // Untuk draft, simpan dengan batch_id = null (akan dialokasikan saat finalisasi)
            // atau simpan batch_id yang dipilih user (untuk referensi)
            PenjualanItem::create([
                'id_penjualan' => $this->record->id_penjualan,
                'id_produk' => $productId,
                'id_pembelian_item' => $batchId > 0 ? $batchId : null,
                'qty' => $qty,
                'harga_jual' => $customPrice,
                'kondisi' => $condition,
                'serials' => ! empty($serials) ? $serials : null,
            ]);
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
        $actions = [];

        // Jika draft, tampilkan tombol update dan finalisasi
        if ($this->record->isDraft()) {
            $actions[] = $this->getSaveFormAction()
                ->label('Update Draft')
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->formId('form');

            $actions[] = \Filament\Actions\Action::make('finalize')
                ->label('Finalisasi')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Finalisasi Draft')
                ->modalDescription('Setelah difinalisasi, stok akan berkurang dan transaksi tidak bisa diubah. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Finalisasi')
                ->action(function () {
                    $this->finalizeDraft();
                });
        }

        $actions[] = $this->getCancelFormAction()
            ->label('Batal')
            ->icon('heroicon-m-x-mark')
            ->color('gray')
            ->formId('form');

        return $actions;
    }

    /**
     * Finalize draft document
     */
    protected function finalizeDraft(): void
    {
        try {
            // Ambil data dari form (karena action tidak melalui mutateFormDataBeforeSave)
            $formData = $this->form->getState();
            $items = $formData['items_temp'] ?? [];

            // Validasi minimal ada 1 item
            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items_temp' => 'Minimal harus ada 1 item produk.',
                ]);
            }

            // Validasi stok tersedia sebelum finalisasi
            $this->validateBeforeSave($items);

            // Set flag finalisasi
            $this->isFinalizing = true;

            DB::transaction(function () use ($items) {
                // Update data header penjualan
                $formData = $this->form->getState();
                $this->record->update([
                    'id_karyawan' => $formData['id_karyawan'] ?? $this->record->id_karyawan,
                    'id_member' => $formData['id_member'] ?? $this->record->id_member,
                    'tanggal_penjualan' => $formData['tanggal_penjualan'] ?? $this->record->tanggal_penjualan,
                    'catatan' => $formData['catatan'] ?? $this->record->catatan,
                    'diskon_total' => $formData['diskon_total'] ?? $this->record->diskon_total ?? 0,
                ]);

                // Finalize dulu (update status ke final dan generate nomor)
                // Ini penting agar hooks PenjualanItem mengenali status final
                $this->record->finalize();

                // Delete existing items (draft items)
                $this->record->items()->delete();

                // Create items with FIFO (stock deduction akan terjadi karena status sudah final)
                $this->createItemsWithFifo($items);

                // Recalculate totals
                $this->record->recalculateTotals();
            });

            Notification::make()
                ->title('Draft berhasil difinalisasi')
                ->body("Transaksi {$this->record->no_nota} telah final.")
                ->icon('heroicon-o-check-circle')
                ->success()
                ->send();

            // Redirect ke view
            $this->redirect(PenjualanResource::getUrl('view', ['record' => $this->record]));

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal finalisasi')
                ->body($e->getMessage())
                ->icon('heroicon-o-exclamation-triangle')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
