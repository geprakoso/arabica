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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CreateTukarTambah extends CreateRecord
{
    protected static string $resource = TukarTambahResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return TukarTambahResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $tanggal = $data['tanggal'] ?? now();
            $catatan = $data['catatan'] ?? null;
            $karyawanId = $data['id_karyawan'] ?? null;
            $penjualanPayload = is_array($data['penjualan'] ?? null) ? $data['penjualan'] : [];
            $pembelianPayload = is_array($data['pembelian'] ?? null) ? $data['pembelian'] : [];

            $penjualan = Penjualan::query()->create([
                'tanggal_penjualan' => $tanggal,
                'catatan' => $penjualanPayload['catatan'] ?? $catatan,
                'id_karyawan' => $penjualanPayload['id_karyawan'] ?? $karyawanId,
                'id_member' => $penjualanPayload['id_member'] ?? null,
                'diskon_total' => $penjualanPayload['diskon_total'] ?? 0,
                'no_nota' => $penjualanPayload['no_nota'] ?? null,
                'sumber_transaksi' => 'tukar_tambah',
            ]);

            $pembelian = Pembelian::query()->create([
                'tanggal' => $tanggal,
                'catatan' => $pembelianPayload['catatan'] ?? $catatan,
                'id_karyawan' => $pembelianPayload['id_karyawan'] ?? $karyawanId,
                'id_supplier' => $pembelianPayload['id_supplier'] ?? null,
                'no_po' => $pembelianPayload['no_po'] ?? null,
                'tipe_pembelian' => $pembelianPayload['tipe_pembelian'] ?? 'non_ppn',
                'jenis_pembayaran' => $pembelianPayload['jenis_pembayaran'] ?? 'lunas',
                'tgl_tempo' => ($pembelianPayload['jenis_pembayaran'] ?? 'lunas') === 'tempo'
                    ? ($pembelianPayload['tgl_tempo'] ?? null)
                    : null,
            ]);

            $this->createPenjualanItems($penjualan, $penjualanPayload['items'] ?? []);
            $this->createPenjualanJasaItems($penjualan, $penjualanPayload['jasa_items'] ?? []);
            $this->createPembelianItems($pembelian, $pembelianPayload['items'] ?? []);
            $this->createPenjualanPembayaran($penjualan, $penjualanPayload['pembayaran'] ?? []);
            $this->createPembelianPembayaran($pembelian, $pembelianPayload['pembayaran'] ?? []);

            return TukarTambah::query()->create([
                'tanggal' => $tanggal,
                'catatan' => $catatan,
                'id_karyawan' => $karyawanId,
                'penjualan_id' => $penjualan->getKey(),
                'pembelian_id' => $pembelian->getKey(),
            ]);
        });
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
            ->sendToDatabase($user);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()->formId('form'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
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

            DB::transaction(function () use ($penjualan, $productId, $qty, $customPrice, $condition, $serials): void {
                $this->fulfillPenjualanUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);
            });
        }
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
        $available = (int) $batches->sum(fn(PembelianItem $batch): int => (int) ($batch->{$qtyColumn} ?? 0));

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'penjualan.items' => 'Qty melebihi stok tersedia (' . $available . ').',
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
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $metode = $item['metode_bayar'] ?? null;
            $jumlah = $item['jumlah'] ?? null;

            if (! $metode || $jumlah === null || $jumlah === '') {
                continue;
            }

            PenjualanPembayaran::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
                'catatan' => $item['catatan'] ?? null,
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
            $jumlah = $item['jumlah'] ?? null;

            if (! $metode || $jumlah === null || $jumlah === '') {
                continue;
            }

            PembelianPembayaran::query()->create([
                'id_pembelian' => $pembelian->getKey(),
                'metode_bayar' => $metode,
                'akun_transaksi_id' => $item['akun_transaksi_id'] ?? null,
                'jumlah' => (int) $jumlah,
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
