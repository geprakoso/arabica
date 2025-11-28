<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\RequestOrder;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        $karyawan = Karyawan::first();
        $supplierA = Supplier::where('nama_supplier', 'PT Sinar Komputindo')->first();
        $supplierB = Supplier::where('nama_supplier', 'CV Mekar Jaya Parts')->first();

        $requestOrderA = RequestOrder::where('no_ro', 'RO-0001')->first();
        $requestOrderB = RequestOrder::where('no_ro', 'RO-0002')->first();

        $pembelians = [
            [
                'no_po' => 'PO-1001',
                'tanggal' => now()->subDays(8),
                'tipe_pembelian' => 'non_ppn',
                'jenis_pembayaran' => 'lunas',
                'tgl_tempo' => null,
                'id_karyawan' => $karyawan?->id,
                'id_supplier' => $supplierA?->id,
                'request_order_id' => $requestOrderA?->id,
                'items' => [
                    [
                        'product_name' => 'Intel Core i7-14700K',
                        'qty' => 20,
                        'hpp' => 6000000,
                        'harga_jual' => 7500000,
                    ],
                    [
                        'product_name' => 'Samsung 990 PRO 2TB NVMe M.2',
                        'qty' => 15,
                        'hpp' => 2800000,
                        'harga_jual' => 3500000,
                    ],
                ],
            ],
            [
                'no_po' => 'PO-1002',
                'tanggal' => now()->subDays(5),
                'tipe_pembelian' => 'ppn',
                'jenis_pembayaran' => 'tempo',
                'tgl_tempo' => now()->addDays(25),
                'id_karyawan' => $karyawan?->id,
                'id_supplier' => $supplierB?->id,
                'request_order_id' => $requestOrderB?->id,
                'items' => [
                    [
                        'product_name' => 'ASUS ROG Strix Z790-E Gaming WiFi',
                        'qty' => 5,
                        'hpp' => 9000000,
                        'harga_jual' => 12000000,
                    ],
                    [
                        'product_name' => 'Corsair RM850e 850W 80+ Gold',
                        'qty' => 10,
                        'hpp' => 2000000,
                        'harga_jual' => 2600000,
                    ],
                ],
            ],
        ];

        foreach ($pembelians as $data) {
            $items = $data['items'] ?? [];
            unset($data['items']);

            $pembelian = Pembelian::updateOrCreate(
                ['no_po' => $data['no_po']],
                $data
            );

            if (! empty($data['request_order_id'])) {
                $pembelian->requestOrders()->syncWithoutDetaching([$data['request_order_id']]);
            }

            foreach ($items as $item) {
                $produk = Produk::where('nama_produk', $item['product_name'])->first();

                if (! $produk) {
                    continue;
                }

                PembelianItem::updateOrCreate(
                    [
                        'id_pembelian' => $pembelian->id_pembelian,
                        'id_produk' => $produk->id,
                    ],
                    [
                        'hpp' => $item['hpp'],
                        'harga_jual' => $item['harga_jual'],
                        'qty' => $item['qty'],
                        'kondisi' => 'baru',
                    ]
                );
            }
        }
    }
}
