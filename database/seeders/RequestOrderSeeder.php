<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Produk;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class RequestOrderSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = Karyawan::first();
        $produkList = Produk::take(3)->get();

        if (! $karyawan || $produkList->isEmpty()) {
            return;
        }

        $orders = [
            [
                'no_ro' => 'RO-0001',
                'tanggal' => now()->subDays(12)->toDateString(),
                'catatan' => 'Permintaan restock produk populer.',
                'karyawan_id' => $karyawan->id,
                'produk_ids' => $produkList->pluck('id')->all(),
            ],
            [
                'no_ro' => 'RO-0002',
                'tanggal' => now()->subDays(9)->toDateString(),
                'catatan' => 'Pengadaan batch kedua.',
                'karyawan_id' => $karyawan->id,
                'produk_ids' => $produkList->take(2)->pluck('id')->all(),
            ],
        ];

        foreach ($orders as $orderData) {
            $produkIds = $orderData['produk_ids'];
            unset($orderData['produk_ids']);

            $order = RequestOrder::updateOrCreate(
                ['no_ro' => $orderData['no_ro']],
                Arr::except($orderData, ['no_ro'])
            );

            foreach ($produkIds as $produkId) {
                RequestOrderItem::updateOrCreate(
                    [
                        'request_order_id' => $order->id,
                        'produk_id' => $produkId,
                    ],
                    []
                );
            }
        }
    }
}
