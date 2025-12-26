<?php

namespace App\Services\POS;

use App\Models\Jasa;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutPosAction
{
    /**
     * Jalankan checkout POS sederhana berbasis inventory + batch.
     *
     * @param  array{
     *     items: array<int, array{
     *         id_produk:int,
     *         qty:int,
     *         harga_jual?:numeric,
     *         kondisi?:string
     *     }>,
     *     services?: array<int, array{
     *         jasa_id:int,
     *         qty:int,
     *         harga?:numeric,
     *         catatan?:string
     *     }>,
     *     diskon_total?:numeric,
     *     metode_bayar?:string,
     *     tunai_diterima?:numeric,
     *     catatan?:string,
     *     id_member?:int,
     *     id_karyawan?:int,
     *     gudang_id?:int,
     *     tanggal_penjualan?:\DateTimeInterface|string
     * } $payload
     */
    public function __invoke(array $payload): Penjualan
    {
        return $this->handle($payload);
    }

    public function handle(array $payload): Penjualan
    {
        $items = Arr::get($payload, 'items', []);
        $services = Arr::get($payload, 'services', []);

        if (blank($items) && blank($services)) {
            throw ValidationException::withMessages([
                'items' => 'Keranjang kosong. Tambahkan produk atau jasa sebelum checkout.',
            ]);
        }

        return DB::transaction(function () use ($payload, $items, $services): Penjualan {
            $penjualan = Penjualan::query()->create([
                'tanggal_penjualan' => Arr::get($payload, 'tanggal_penjualan', now()),
                'catatan' => Arr::get($payload, 'catatan'),
                'id_member' => Arr::get($payload, 'id_member'),
                'id_karyawan' => Arr::get($payload, 'id_karyawan'),
                'metode_bayar' => Arr::get($payload, 'metode_bayar'),
                'tunai_diterima' => Arr::get($payload, 'tunai_diterima'),
                'gudang_id' => Arr::get($payload, 'gudang_id'),
                'sumber_transaksi' => 'pos',
            ]);

            $total = 0;

            foreach ($items as $index => $itemData) {
                $qty = (int) Arr::get($itemData, 'qty', 0);
                $produkId = Arr::get($itemData, 'id_produk');

                if ($qty < 1 || ! $produkId) {
                    throw ValidationException::withMessages([
                        "items.$index" => 'Item tidak valid atau qty kosong.',
                    ]);
                }

                $hargaJual = Arr::get($itemData, 'harga_jual');
                $hargaJual = ($hargaJual === '' || is_null($hargaJual)) ? null : (int) $hargaJual;
                $kondisi = Arr::get($itemData, 'kondisi');

                $lineTotal = $this->fulfillItemUsingFifo(
                    penjualan: $penjualan,
                    productId: (int) $produkId,
                    qty: $qty,
                    customPrice: $hargaJual,
                    kondisi: $kondisi,
                    itemIndex: $index,
                );

                $total += $lineTotal;
            }

            foreach ($services as $index => $serviceData) {
                $jasaId = Arr::get($serviceData, 'jasa_id');
                $qty = max(1, (int) Arr::get($serviceData, 'qty', 1));

                if ($jasaId < 1) {
                    throw ValidationException::withMessages([
                        "services.$index.jasa_id" => 'Pilih jasa terlebih dahulu.',
                    ]);
                }

                $harga = Arr::get($serviceData, 'harga');
                if ($harga === '' || $harga === null) {
                    $harga = Jasa::query()->whereKey($jasaId)->value('harga');
                }

                $harga = (int) ($harga ?? 0);

                PenjualanJasa::query()->create([
                    'id_penjualan' => $penjualan->getKey(),
                    'jasa_id' => $jasaId,
                    'qty' => $qty,
                    'harga' => $harga,
                    'catatan' => Arr::get($serviceData, 'catatan'),
                ]);

                $total += $harga * $qty;
            }

            $diskonTotal = (int) Arr::get($payload, 'diskon_total', 0);
            $diskonTotal = min(max($diskonTotal, 0), $total);

            $grandTotal = max(0, $total - $diskonTotal);
            $tunaiDiterima = Arr::get($payload, 'tunai_diterima');
            $kembalian = is_null($tunaiDiterima) ? null : max(0, (int) $tunaiDiterima - $grandTotal);

            $penjualan->update([
                'total' => $total,
                'diskon_total' => $diskonTotal,
                'grand_total' => $grandTotal,
                'kembalian' => $kembalian,
            ]);

            return $penjualan->load(['items.produk', 'items.pembelianItem', 'jasaItems.jasa']);
        });
    }

    protected function fulfillItemUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $kondisi, int $itemIndex): int
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $batchesQuery = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->orderBy('id_pembelian_item')
            ->lockForUpdate();

        if ($kondisi) {
            $batchesQuery->where('kondisi', $kondisi);
        }

        $batches = $batchesQuery->get();

        $availableQty = (int) $batches->sum(fn ($batch) => (int) ($batch->{$qtyColumn} ?? 0));

        if ($availableQty < $qty) {
            throw ValidationException::withMessages([
                "items.$itemIndex.qty" => 'Stok produk tidak mencukupi untuk kuantitas yang diminta.',
            ]);
        }

        $remaining = $qty;
        $lineTotal = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);
            $unitPrice = $customPrice ?? (int) $batch->harga_jual;

            PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->getKey(),
                'qty' => $takeQty,
                'harga_jual' => $customPrice,
                'kondisi' => $kondisi,
            ]);

            $lineTotal += $unitPrice * $takeQty;
            $remaining -= $takeQty;
        }

        return $lineTotal;
    }

}
