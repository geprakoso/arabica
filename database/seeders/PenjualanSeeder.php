<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Member;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Produk;
use Illuminate\Database\Seeder;

class PenjualanSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = Karyawan::first();
        $members = Member::take(2)->get();

        if (! $karyawan || $members->isEmpty()) {
            return;
        }

        $penjualans = [
            [
                'no_nota' => 'PJ-SEED-0001',
                'tanggal_penjualan' => now()->subDays(2),
                'catatan' => 'Penjualan ritel lewat kasir.',
                'id_karyawan' => $karyawan->id,
                'id_member' => $members->get(0)?->id,
                'items' => [
                    ['product_name' => 'Intel Core i7-14700K', 'qty' => 2],
                    ['product_name' => 'Samsung 990 PRO 2TB NVMe M.2', 'qty' => 1],
                ],
            ],
            [
                'no_nota' => 'PJ-SEED-0002',
                'tanggal_penjualan' => now()->subDay(),
                'catatan' => 'Penjualan online marketplace.',
                'id_karyawan' => $karyawan->id,
                'id_member' => $members->get(1)?->id,
                'items' => [
                    ['product_name' => 'ASUS ROG Strix Z790-E Gaming WiFi', 'qty' => 1],
                    ['product_name' => 'Corsair RM850e 850W 80+ Gold', 'qty' => 1],
                ],
            ],
        ];

        foreach ($penjualans as $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $penjualan = Penjualan::firstOrCreate(
                ['no_nota' => $data['no_nota']],
                $data
            );

            foreach ($items as $itemData) {
                $batch = $this->findBatch($itemData['product_name']);

                if (! $batch) {
                    continue;
                }

                PenjualanItem::firstOrCreate(
                    [
                        'id_penjualan' => $penjualan->id_penjualan,
                        'id_produk' => $batch->produk_id ?? $batch->id_produk,
                        'id_pembelian_item' => $batch->id_pembelian_item,
                    ],
                    [
                        'qty' => $itemData['qty'],
                        'hpp' => $batch->hpp,
                        'harga_jual' => $batch->harga_jual,
                        'kondisi' => $batch->kondisi ?? 'baru',
                    ]
                );
            }
        }
    }

    protected function findBatch(string $productName): ?PembelianItem
    {
        $produk = Produk::where('nama_produk', $productName)->first();

        if (! $produk) {
            return null;
        }

        return PembelianItem::where('id_produk', $produk->id)
            ->orderByDesc('id_pembelian_item')
            ->first();
    }
}
