<?php

namespace App\Services\POS;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
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
     *         id_pembelian_item:int,
     *         qty:int,
     *         harga_jual?:numeric,
     *         diskon?:numeric,
     *         kondisi?:string
     *     }>,
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
        if (blank($items)) {
            throw ValidationException::withMessages([
                'items' => 'Keranjang kosong. Tambahkan item sebelum checkout.',
            ]);
        }

        return DB::transaction(function () use ($payload, $items): Penjualan {
            $penjualan = Penjualan::query()->create([
                'tanggal_penjualan' => Arr::get($payload, 'tanggal_penjualan', now()),
                'catatan' => Arr::get($payload, 'catatan'),
                'id_member' => Arr::get($payload, 'id_member'),
                'id_karyawan' => Arr::get($payload, 'id_karyawan'),
                'metode_bayar' => Arr::get($payload, 'metode_bayar'),
                'tunai_diterima' => Arr::get($payload, 'tunai_diterima'),
                'gudang_id' => Arr::get($payload, 'gudang_id'),
            ]);

            $total = 0;
            $diskonTotal = 0;

            foreach ($items as $index => $itemData) {
                $qty = (int) Arr::get($itemData, 'qty', 0);
                $produkId = Arr::get($itemData, 'id_produk');
                $batchId = Arr::get($itemData, 'id_pembelian_item');

                if ($qty < 1 || ! $produkId || ! $batchId) {
                    throw ValidationException::withMessages([
                        "items.$index" => 'Item tidak valid atau qty kosong.',
                    ]);
                }

                $hargaJual = Arr::get($itemData, 'harga_jual');
                $diskon = (float) Arr::get($itemData, 'diskon', 0);

                PenjualanItem::query()->create([
                    'id_penjualan' => $penjualan->getKey(),
                    'id_produk' => $produkId,
                    'id_pembelian_item' => $batchId,
                    'qty' => $qty,
                    'harga_jual' => $hargaJual, // Jika null akan diisi default dari batch (lihat model).
                    'kondisi' => Arr::get($itemData, 'kondisi'),
                ]);

                $subtotal = ($hargaJual ?? 0) * $qty;
                $total += $subtotal;
                $diskonTotal += $diskon;
            }

            $grandTotal = max(0, $total - $diskonTotal);
            $tunaiDiterima = Arr::get($payload, 'tunai_diterima');
            $kembalian = is_null($tunaiDiterima) ? null : max(0, (float) $tunaiDiterima - $grandTotal);

            $penjualan->update([
                'total' => $total,
                'diskon_total' => $diskonTotal,
                'grand_total' => $grandTotal,
                'kembalian' => $kembalian,
            ]);

            return $penjualan->load(['items.produk', 'items.pembelianItem']);
        });
    }
}
