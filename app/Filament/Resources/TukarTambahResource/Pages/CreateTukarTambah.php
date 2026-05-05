<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\PembelianPembayaran;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use App\Models\PenjualanPembayaran;
use App\Models\TukarTambah;
use App\Services\ValidationLogger;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTukarTambah extends CreateRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected static bool $canCreateAnother = false;

    public string $saveMode = 'draft';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawState = $this->form->getRawState();
        if (isset($rawState['pembayaran']) && is_array($rawState['pembayaran'])) {
            $data['pembayaran'] = $rawState['pembayaran'];
        }

        // Ensure no_nota is set
        if (empty($data['no_nota'])) {
            $data['no_nota'] = TukarTambah::generateNoNota();
        }

        // VALIDASI: cek duplikat dan stok penjualan items
        $penjualanItems = $data['penjualan']['items'] ?? [];
        if (! empty($penjualanItems)) {
            $this->validatePenjualanItems($penjualanItems);
        }

        return $data;
    }

    /**
     * Validasi duplikat dan stok untuk penjualan items
     */
    protected function validatePenjualanItems(array $items): void
    {
        // Start batch logging
        ValidationLogger::startBatch();

        if (empty($items)) {
            // Log validation error
            ValidationLogger::logMinimumItems(
                sourceType: 'TukarTambah',
                sourceAction: 'create',
                minRequired: 1,
                currentCount: 0
            );

            throw ValidationException::withMessages([
                'penjualan.items' => 'Minimal harus ada 1 item produk.',
            ]);
        }

        // Cek duplikat produk - tidak boleh ada produk+batch+kondisi yang sama persis
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
                        sourceType: 'TukarTambah',
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
                        'penjualan.items' => $errorMessage,
                    ]);
                }
                $productKeys[$key] = $index + 1;
            }
        }

        // Aggregate total qty per produk (dengan batch dan kondisi)
        $totalQtyMap = [];
        foreach ($items as $index => $item) {
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
                    'rows' => [],
                ];
            }
            $totalQtyMap[$key]['qty'] += $qty;
            $totalQtyMap[$key]['rows'][] = $index + 1;
        }

        // Validasi stok tersedia menggunakan StockBatch (sama seperti Penjualan standar)
        foreach ($totalQtyMap as $group) {
            $productId = $group['product_id'];
            $batchId = $group['batch_id'];
            $requestedQty = $group['qty'];
            $rows = $group['rows'];

            $query = \App\Models\StockBatch::query()
                ->whereHas('pembelianItem', function ($q) use ($productId) {
                    $q->where('id_produk', $productId);
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
                    sourceType: 'TukarTambah',
                    sourceAction: 'create',
                    productName: $productName,
                    available: $availableQty,
                    requested: $requestedQty,
                    inputData: [
                        'product_id' => $productId,
                        'batch_id' => $batchId,
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
                    'penjualan.items' => $errorMessage,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return TukarTambahResource::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                $tanggal = $data['tanggal'] ?? now();
                $catatan = $data['catatan'] ?? null;
                $karyawanId = $data['id_karyawan'] ?? null;
                $penjualanPayload = is_array($data['penjualan'] ?? null) ? $data['penjualan'] : [];
                $pembelianPayload = is_array($data['pembelian'] ?? null) ? $data['pembelian'] : [];

                // Generate TukarTambah nota number first
                $ttNotaNumber = $data['no_nota'] ?? TukarTambah::generateNoNota();

                // Use TukarTambah nota for both Penjualan and Pembelian
                $penjualan = Penjualan::query()->create([
                    'tanggal_penjualan' => $tanggal,
                    'catatan' => $penjualanPayload['catatan'] ?? $catatan,
                    'id_karyawan' => $penjualanPayload['id_karyawan'] ?? $karyawanId,
                    'id_member' => $data['id_member'] ?? null,
                    'diskon_total' => $penjualanPayload['diskon_total'] ?? 0,
                    'no_nota' => $ttNotaNumber,
                    'sumber_transaksi' => 'tukar_tambah',
                ]);

                $pembelian = Pembelian::query()->create([
                    'tanggal' => $tanggal,
                    'catatan' => $pembelianPayload['catatan'] ?? $catatan,
                    'id_karyawan' => $pembelianPayload['id_karyawan'] ?? $karyawanId,
                    'id_supplier' => $pembelianPayload['id_supplier'] ?? null,
                    'no_po' => $ttNotaNumber,
                    'tipe_pembelian' => $pembelianPayload['tipe_pembelian'] ?? 'non_ppn',
                ]);

                $this->createPenjualanItems($penjualan, $penjualanPayload['items'] ?? []);
                $this->createPenjualanJasaItems($penjualan, $penjualanPayload['jasa_items'] ?? []);
                $this->createPembelianItems($pembelian, $pembelianPayload['items'] ?? []);
                $allPayments = $data['pembayaran'] ?? [];
                $penjualanPayments = collect($allPayments)->where('tipe', 'penjualan')->values()->all();
                $pembelianPayments = collect($allPayments)->where('tipe', 'pembelian')->values()->all();
                $this->createPenjualanPembayaran($penjualan, $penjualanPayments);
                $this->createPembelianPembayaran($pembelian, $pembelianPayments);

                // Determine status based on save mode
                $isFinal = $this->saveMode === 'final';
                $statusDokumen = $isFinal ? 'final' : 'draft';

                // Create TukarTambah with the same nota
                $tukarTambah = TukarTambah::query()->create([
                    'no_nota' => $ttNotaNumber,
                    'tanggal' => $tanggal,
                    'catatan' => $catatan,
                    'id_karyawan' => $karyawanId,
                    'status_dokumen' => $statusDokumen,
                    'is_locked' => $isFinal,
                    'posted_at' => $isFinal ? now() : null,
                    'posted_by_id' => $isFinal ? auth()->id() : null,
                    'penjualan_id' => $penjualan->getKey(),
                    'pembelian_id' => $pembelian->getKey(),
                ]);

                // If final mode, sync Penjualan & Pembelian status
                if ($isFinal) {
                    $penjualan->update(['status_dokumen' => 'final']);
                    $pembelian->update(['is_locked' => true]);
                }

                return $tukarTambah;
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $penjualanNota = $this->record->penjualan?->no_nota ?? '-';
        $pembelianNota = $this->record->pembelian?->no_po ?? '-';
        $modeLabel = $this->saveMode === 'final' ? 'Final' : 'Draft';
        $statusColor = $this->saveMode === 'final' ? 'success' : 'warning';

        Notification::make()
            ->title("Tukar tambah {$modeLabel} berhasil disimpan")
            ->body("Nota Penjualan: {$penjualanNota} • Nota Pembelian: {$pembelianNota}")
            ->icon('heroicon-o-check-circle')
            ->{$statusColor}()
            ->actions([
                Action::make('Lihat')
                    ->url(TukarTambahResource::getUrl('view', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
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
            \Filament\Actions\Action::make('saveDraft')
                ->label('Simpan Draft')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->action('saveDraft'),
            \Filament\Actions\Action::make('saveFinal')
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

    protected function createPenjualanItems(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);
            $batchId = (int) ($item['id_pembelian_item'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $customPrice = $item['harga_jual'] ?? null;
            $customPrice = ($customPrice === '' || $customPrice === null) ? null : (int) $customPrice;
            $condition = $item['kondisi'] ?? null;
            $serials = is_array($item['serials'] ?? null) ? array_values($item['serials']) : [];

            if (! empty($serials) && count($serials) !== $qty) {
                throw ValidationException::withMessages([
                    'penjualan.items' => 'Jumlah SN harus sama dengan Qty.',
                ]);
            }

            DB::transaction(function () use ($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials): void {
                if ($batchId > 0) {
                    $this->fulfillPenjualanWithBatch($penjualan, $productId, $qty, $batchId, $customPrice, $condition, $serials);
                } else {
                    $this->fulfillPenjualanUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);
                }
            });
        }
    }

    protected function fulfillPenjualanWithBatch(Penjualan $penjualan, int $productId, int $qty, int $batchId, ?int $customPrice, ?string $condition, array $serials): void
    {
        $stockBatch = \App\Models\StockBatch::query()
            ->where('pembelian_item_id', $batchId)
            ->lockForUpdate()
            ->first();

        if (! $stockBatch) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Batch stok tidak ditemukan.',
            ]);
        }

        if ($stockBatch->qty_available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => "Stok batch tidak cukup. Tersedia: {$stockBatch->qty_available}, Dibutuhkan: {$qty}",
            ]);
        }

        // Stock decrement is handled by PenjualanItem::created model event
        // Do NOT manually decrement here to avoid double-deduction

        $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

        PenjualanItem::query()->create([
            'id_penjualan' => $penjualan->getKey(),
            'id_produk' => $productId,
            'id_pembelian_item' => $batchId,
            'qty' => $qty,
            'harga_jual' => $customPrice,
            'kondisi' => $stockBatch->pembelianItem?->kondisi ?? $condition,
            'serials' => empty($takeSerials) ? null : $takeSerials,
        ]);
    }

    protected function fulfillPenjualanUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials): Collection
    {
        $batchesQuery = \App\Models\StockBatch::query()
            ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
                $q->where('id_produk', $productId);
                if ($condition) {
                    $q->where('kondisi', $condition);
                }
            })
            ->where('qty_available', '>', 0)
            ->orderBy('id')
            ->lockForUpdate();

        $batches = $batchesQuery->get();
        $available = (int) $batches->sum(fn (\App\Models\StockBatch $batch): int => $batch->qty_available);

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Qty melebihi stok tersedia ('.$available.').',
            ]);
        }

        $remaining = $qty;
        $created = collect();
        $serials = array_values($serials);

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

            // Stock decrement is handled by PenjualanItem::created model event
            // Do NOT manually decrement here to avoid double-deduction

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $stockBatch->pembelian_item_id,
                'qty' => $takeQty,
                'harga_jual' => $customPrice,
                'kondisi' => $condition,
                'serials' => empty($takeSerials) ? null : $takeSerials,
            ]);

            $created->push($record);
            $remaining -= $takeQty;
        }

        return $created;
    }

    protected function createPembelianItems(Pembelian $pembelian, array $items): void
    {
        $productColumn = PembelianItem::productForeignKey();
        $qtyMasukColumn = PembelianItem::qtyMasukColumn();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $data = [
                'id_pembelian' => $pembelian->getKey(),
                $productColumn => $productId,
                'qty' => $qty,
                'hpp' => (int) ($item['hpp'] ?? 0),
                'harga_jual' => (int) ($item['harga_jual'] ?? 0),
                'kondisi' => $item['kondisi'] ?? 'baru',
            ];

            if ($qtyMasukColumn !== 'qty') {
                $data[$qtyMasukColumn] = $qty;
            }

            if ($qtySisaColumn !== 'qty') {
                $data[$qtySisaColumn] = $qty;
            }

            PembelianItem::query()->create($data);
        }
    }

    protected function normalizePaymentAmount(mixed $value): int
    {
        if (is_array($value)) {
            $value = collect($value)
                ->flatten()
                ->filter(fn ($amount): bool => filled($amount))
                ->first();
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        $cleaned = preg_replace('/[^0-9]/', '', (string) ($value ?? ''));

        return (int) ($cleaned ?: 0);
    }

    protected function normalizePaymentFilePath(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value === '' ? null : $value;
        }

        if (is_array($value)) {
            $first = collect($value)
                ->flatten()
                ->filter(fn ($path): bool => is_string($path) && $path !== '')
                ->first();

            return $first ?: null;
        }

        return null;
    }

    protected function createPenjualanPembayaran(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $this->normalizePaymentAmount($item['jumlah'] ?? null);

            if (! $metode || $jumlah <= 0) {
                continue;
            }

            PenjualanPembayaran::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => $jumlah,
                'catatan' => $item['catatan'] ?? null,
                'bukti_transfer' => $this->normalizePaymentFilePath($item['bukti_transfer'] ?? null),
            ]);
        }
    }

    protected function createPembelianPembayaran(Pembelian $pembelian, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $this->normalizePaymentAmount($item['jumlah'] ?? null);

            if (! $metode || $jumlah <= 0) {
                continue;
            }

            PembelianPembayaran::query()->create([
                'id_pembelian' => $pembelian->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => $jumlah,
                'catatan' => $item['catatan'] ?? null,
                'bukti_transfer' => $this->normalizePaymentFilePath($item['bukti_transfer'] ?? null),
            ]);
        }
    }

    protected function createPenjualanJasaItems(Penjualan $penjualan, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $jasaId = (int) ($item['jasa_id'] ?? 0);
            $qty = (int) ($item['qty'] ?? 1);
            $harga = (int) ($item['harga'] ?? 0);

            if ($jasaId < 1 || $qty < 1) {
                continue;
            }

            PenjualanJasa::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'jasa_id' => $jasaId,
                'qty' => $qty,
                'harga' => $harga,
                'catatan' => $item['catatan'] ?? null,
            ]);
        }
    }
}
