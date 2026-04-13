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
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateTukarTambah extends CreateRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('TukarTambah: mutateFormDataBeforeCreate', [
            'data_keys' => array_keys($data),
            'no_nota' => $data['no_nota'] ?? 'NOT SET',
            'tanggal' => $data['tanggal'] ?? 'NOT SET',
            'id_karyawan' => $data['id_karyawan'] ?? 'NOT SET',
            'id_member' => $data['id_member'] ?? 'NOT SET',
        ]);

        // Ensure no_nota is set
        if (empty($data['no_nota'])) {
            $data['no_nota'] = TukarTambah::generateNoNota();
            Log::info('TukarTambah: Generated no_nota', ['no_nota' => $data['no_nota']]);
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
        if (empty($items)) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Minimal harus ada 1 item produk.',
            ]);
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

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

        // Validasi stok tersedia
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
        Log::info('TukarTambah: Starting record creation', [
            'data_keys' => array_keys($data),
            'has_penjualan' => isset($data['penjualan']),
            'has_pembelian' => isset($data['pembelian']),
            'has_unified_pembayaran' => isset($data['unified_pembayaran']),
        ]);

        try {
            return DB::transaction(function () use ($data) {
                $tanggal = $data['tanggal'] ?? now();
                $catatan = $data['catatan'] ?? null;
                $karyawanId = $data['id_karyawan'] ?? null;
                $penjualanPayload = is_array($data['penjualan'] ?? null) ? $data['penjualan'] : [];
                $pembelianPayload = is_array($data['pembelian'] ?? null) ? $data['pembelian'] : [];

                Log::info('TukarTambah: Payload prepared', [
                    'penjualan_items_count' => count($penjualanPayload['items'] ?? []),
                    'pembelian_items_count' => count($pembelianPayload['items'] ?? []),
                ]);

                // Process unified payments and split them
                $unifiedPayments = $data['unified_pembayaran'] ?? [];
                $penjualanPayments = [];
                $pembelianPayments = [];

                foreach ($unifiedPayments as $payment) {
                    if (! is_array($payment)) {
                        continue;
                    }

                    $tipeTransaksi = $payment['tipe_transaksi'] ?? null;

                    // Remove tipe_transaksi from payment data before saving
                    $paymentData = $payment;
                    unset($paymentData['tipe_transaksi']);

                    if ($tipeTransaksi === 'penjualan') {
                        $penjualanPayments[] = $paymentData;
                    } elseif ($tipeTransaksi === 'pembelian') {
                        $pembelianPayments[] = $paymentData;
                    }
                }

                // Override pembayaran arrays with unified payments
                $penjualanPayload['pembayaran'] = $penjualanPayments;
                $pembelianPayload['pembayaran'] = $pembelianPayments;

                // Generate TukarTambah nota number first
                $ttNotaNumber = $data['no_nota'] ?? TukarTambah::generateNoNota();

                Log::info('TukarTambah: Creating Penjualan', ['no_nota' => $ttNotaNumber]);

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

                Log::info('TukarTambah: Penjualan created', ['id' => $penjualan->getKey()]);

                $pembelian = Pembelian::query()->create([
                    'tanggal' => $tanggal,
                    'catatan' => $pembelianPayload['catatan'] ?? $catatan,
                    'id_karyawan' => $pembelianPayload['id_karyawan'] ?? $karyawanId,
                    'id_supplier' => $pembelianPayload['id_supplier'] ?? null,
                    'no_po' => $ttNotaNumber,
                    'tipe_pembelian' => $pembelianPayload['tipe_pembelian'] ?? 'non_ppn',
                ]);

                Log::info('TukarTambah: Pembelian created', ['id' => $pembelian->getKey()]);

                $this->createPenjualanItems($penjualan, $penjualanPayload['items'] ?? []);
                $this->createPenjualanJasaItems($penjualan, $penjualanPayload['jasa_items'] ?? []);
                $this->createPembelianItems($pembelian, $pembelianPayload['items'] ?? []);
                $this->createPenjualanPembayaran($penjualan, $penjualanPayload['pembayaran'] ?? []);
                $this->createPembelianPembayaran($pembelian, $pembelianPayload['pembayaran'] ?? []);

                Log::info('TukarTambah: Creating TukarTambah record');

                // Create TukarTambah with the same nota
                $tukarTambah = TukarTambah::query()->create([
                    'no_nota' => $ttNotaNumber,
                    'tanggal' => $tanggal,
                    'catatan' => $catatan,
                    'id_karyawan' => $karyawanId,
                    'penjualan_id' => $penjualan->getKey(),
                    'pembelian_id' => $pembelian->getKey(),
                ]);

                Log::info('TukarTambah: Record created successfully', ['id' => $tukarTambah->getKey()]);

                return $tukarTambah;
            });
        } catch (\Exception $e) {
            Log::error('TukarTambah: Error during record creation', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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

        Notification::make()
            ->title('Tukar tambah baru dibuat')
            ->body("Nota Penjualan: {$penjualanNota} • Nota Pembelian: {$pembelianNota}")
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(TukarTambahResource::getUrl('view', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Buat')
                ->icon('heroicon-o-plus')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->formId('form')
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
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
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $batch = PembelianItem::query()
            ->where($productColumn, $productId)
            ->whereKey($batchId)
            ->lockForUpdate()
            ->first();

        if (! $batch) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Batch pembelian tidak ditemukan.',
            ]);
        }

        $available = (int) ($batch->{$qtyColumn} ?? 0);

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => "Stok batch tidak cukup. Tersedia: {$available}, Dibutuhkan: {$qty}",
            ]);
        }

        $takeSerials = ! empty($serials) ? array_splice($serials, 0, $qty) : [];

        PenjualanItem::query()->create([
            'id_penjualan' => $penjualan->getKey(),
            'id_produk' => $productId,
            'id_pembelian_item' => $batch->getKey(),
            'qty' => $qty,
            'harga_jual' => $customPrice,
            'kondisi' => $batch->kondisi,
            'serials' => empty($takeSerials) ? null : $takeSerials,
        ]);
    }

    protected function fulfillPenjualanUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials): Collection
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $batchesQuery = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->orderBy('id_pembelian_item')
            ->lockForUpdate();

        if ($condition) {
            $batchesQuery->where('kondisi', $condition);
        }

        $batches = $batchesQuery->get();
        $available = (int) $batches->sum(fn (PembelianItem $batch): int => (int) ($batch->{$qtyColumn} ?? 0));

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Qty melebihi stok tersedia ('.$available.').',
            ]);
        }

        $remaining = $qty;
        $created = collect();
        $serials = array_values($serials);

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);
            $takeSerials = [];

            if (! empty($serials)) {
                $takeSerials = array_splice($serials, 0, $takeQty);
            }

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->getKey(),
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

    protected function createPenjualanPembayaran(Penjualan $penjualan, array $items): void
    {
        Log::info('createPenjualanPembayaran called', ['items_count' => count($items), 'items' => $items]);

        foreach ($items as $item) {
            if (! is_array($item)) {
                Log::warning('Penjualan Payment: Skipped non-array item', ['item' => $item]);

                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $item['jumlah'] ?? null;

            Log::info('Penjualan Payment: Processing', [
                'metode' => $metode,
                'jumlah' => $jumlah,
                'jumlah_int' => (int) $jumlah,
            ]);

            // Skip if no payment method or amount
            if (! $metode || $jumlah === null || $jumlah === '' || (int) $jumlah <= 0) {
                Log::warning('Penjualan Payment: Skipped due to validation', [
                    'metode' => $metode,
                    'jumlah' => $jumlah,
                ]);

                continue;
            }

            $payment = PenjualanPembayaran::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'bukti_transfer' => $item['bukti_transfer'] ?? null,
                'catatan' => $item['catatan'] ?? null,
            ]);

            Log::info('Penjualan Payment: Created', ['id' => $payment->id_penjualan_pembayaran, 'jumlah' => $payment->jumlah]);
        }
    }

    protected function createPembelianPembayaran(Pembelian $pembelian, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $item['jumlah'] ?? null;

            // Skip if no payment method or amount
            if (! $metode || $jumlah === null || $jumlah === '' || (int) $jumlah <= 0) {
                continue;
            }

            PembelianPembayaran::query()->create([
                'id_pembelian' => $pembelian->getKey(),
                'tanggal' => $item['tanggal'] ?? now(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'bukti_transfer' => $item['bukti_transfer'] ?? null,
                'catatan' => $item['catatan'] ?? null,
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
