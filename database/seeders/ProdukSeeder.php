<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriList = collect([
            'Processor',
            'Motherboard',
            'RAM',
            'Storage',
            'Graphics Card',
            'Power Supply',
            'PC Case',
            'Cooling',
        ])->mapWithKeys(fn (string $nama) => [
            $nama => Kategori::firstOrCreate(
                ['nama_kategori' => $nama],
                [
                    'slug' => Str::slug($nama),
                    'is_active' => true,
                ]
            ),
        ]);

        $brandList = collect([
            'Intel',
            'AMD',
            'ASUS',
            'MSI',
            'Corsair',
            'Samsung',
            'Seagate',
            'NVIDIA',
            'NZXT',
            'Noctua',
        ])->mapWithKeys(fn (string $nama) => [
            $nama => Brand::firstOrCreate(
                ['nama_brand' => $nama],
                [
                    'slug' => Str::slug($nama),
                    'is_active' => true,
                ]
            ),
        ]);

        $products = [
            [
                'nama_produk' => 'Intel Core i7-14700K',
                'kategori' => 'Processor',
                'brand' => 'Intel',
                'berat' => 0.30,
                'panjang' => 12.50,
                'lebar' => 10.00,
                'tinggi' => 8.00,
                'deskripsi' => '14 core (20 thread) Raptor Lake refresh, base clock 3.4 GHz, boost 5.6 GHz, socket LGA1700.',
            ],
            [
                'nama_produk' => 'AMD Ryzen 7 7800X3D',
                'kategori' => 'Processor',
                'brand' => 'AMD',
                'berat' => 0.30,
                'panjang' => 12.50,
                'lebar' => 10.00,
                'tinggi' => 8.00,
                'deskripsi' => '8 core (16 thread) Zen 4 dengan 3D V-Cache 96MB, base clock 4.2 GHz, boost 5.0 GHz, socket AM5.',
            ],
            [
                'nama_produk' => 'ASUS ROG Strix Z790-E Gaming WiFi',
                'kategori' => 'Motherboard',
                'brand' => 'ASUS',
                'berat' => 1.20,
                'panjang' => 30.50,
                'lebar' => 24.40,
                'tinggi' => 5.00,
                'deskripsi' => 'Motherboard ATX LGA1700, DDR5, PCIe 5.0 x16, WiFi 6E, 18+1 power stage untuk Intel 13th/14th gen.',
            ],
            [
                'nama_produk' => 'MSI MAG B650 Tomahawk WiFi',
                'kategori' => 'Motherboard',
                'brand' => 'MSI',
                'berat' => 1.05,
                'panjang' => 30.50,
                'lebar' => 24.40,
                'tinggi' => 5.00,
                'deskripsi' => 'Motherboard ATX AM5 dengan VRM 14+2+1, DDR5, PCIe 5.0 NVMe, WiFi 6E, cocok untuk Ryzen 7000 series.',
            ],
            [
                'nama_produk' => 'Corsair Vengeance RGB 32GB (2x16GB) DDR5-6000',
                'kategori' => 'RAM',
                'brand' => 'Corsair',
                'berat' => 0.25,
                'panjang' => 13.50,
                'lebar' => 1.00,
                'tinggi' => 4.00,
                'deskripsi' => 'Kit dual-channel DDR5 CL30 dengan XMP/EXPO, heatsink aluminium dan pencahayaan RGB iCUE.',
            ],
            [
                'nama_produk' => 'Samsung 990 PRO 2TB NVMe M.2',
                'kategori' => 'Storage',
                'brand' => 'Samsung',
                'berat' => 0.05,
                'panjang' => 8.00,
                'lebar' => 2.20,
                'tinggi' => 0.30,
                'deskripsi' => 'SSD NVMe PCIe 4.0 x4, kecepatan baca sampai 7450 MB/s dan tulis 6900 MB/s, form factor M.2 2280.',
            ],
            [
                'nama_produk' => 'Seagate FireCuda 530 2TB NVMe M.2',
                'kategori' => 'Storage',
                'brand' => 'Seagate',
                'berat' => 0.05,
                'panjang' => 8.00,
                'lebar' => 2.20,
                'tinggi' => 0.30,
                'deskripsi' => 'SSD NVMe PCIe 4.0 x4 dengan TBW tinggi untuk workload berat, form factor M.2 2280.',
            ],
            [
                'nama_produk' => 'NVIDIA GeForce RTX 4070 Ti SUPER Founders Edition',
                'kategori' => 'Graphics Card',
                'brand' => 'NVIDIA',
                'berat' => 1.80,
                'panjang' => 30.00,
                'lebar' => 12.00,
                'tinggi' => 6.00,
                'deskripsi' => 'GPU Ada Lovelace dengan 16GB GDDR6X, DLSS 3 frame generation, TGP 285W.',
            ],
            [
                'nama_produk' => 'MSI GeForce RTX 4060 Ti Gaming X 8G',
                'kategori' => 'Graphics Card',
                'brand' => 'MSI',
                'berat' => 1.20,
                'panjang' => 33.00,
                'lebar' => 14.00,
                'tinggi' => 6.00,
                'deskripsi' => 'GPU Ada Lovelace dengan pendingin Twin Frozr 9, boost clock 2670 MHz, konsumsi 220W.',
            ],
            [
                'nama_produk' => 'Corsair RM850e 850W 80+ Gold',
                'kategori' => 'Power Supply',
                'brand' => 'Corsair',
                'berat' => 1.70,
                'panjang' => 16.00,
                'lebar' => 15.00,
                'tinggi' => 8.60,
                'deskripsi' => 'PSU modular 850W dengan sertifikasi 80+ Gold, kabel ATX 3.0/PCIe 5.0 siap untuk GPU RTX 40-series.',
            ],
            [
                'nama_produk' => 'NZXT H7 Flow White',
                'kategori' => 'PC Case',
                'brand' => 'NZXT',
                'berat' => 10.20,
                'panjang' => 48.00,
                'lebar' => 23.00,
                'tinggi' => 50.00,
                'deskripsi' => 'Mid-tower ATX dengan airflow tinggi, panel mesh, ruang radiator 360mm di depan/atas.',
            ],
            [
                'nama_produk' => 'Noctua NH-D15 chromax.black',
                'kategori' => 'Cooling',
                'brand' => 'Noctua',
                'berat' => 1.30,
                'panjang' => 16.00,
                'lebar' => 15.00,
                'tinggi' => 16.50,
                'deskripsi' => 'Air cooler dual-tower legendaris dengan dua kipas 140mm, performa tinggi dan suara senyap.',
            ],
        ];

        foreach ($products as $product) {
            $kategori = $kategoriList[$product['kategori']] ?? null;
            $brand = $brandList[$product['brand']] ?? null;

            if (! $kategori || ! $brand) {
                continue;
            }

            // Ensure SKU is present on insert (required, non-null, unique).
            $model = Produk::firstOrCreate(
                ['nama_produk' => $product['nama_produk']],
                [
                    'kategori_id' => $kategori->id,
                    'brand_id' => $brand->id,
                    'sku' => Produk::generateSku(),
                    'berat' => $product['berat'],
                    'panjang' => $product['panjang'],
                    'lebar' => $product['lebar'],
                    'tinggi' => $product['tinggi'],
                    'deskripsi' => $product['deskripsi'],
                ]
            );

            // Update other fields on existing rows without overriding SKU.
            $model->fill([
                'kategori_id' => $kategori->id,
                'brand_id' => $brand->id,
                'berat' => $product['berat'],
                'panjang' => $product['panjang'],
                'lebar' => $product['lebar'],
                'tinggi' => $product['tinggi'],
                'deskripsi' => $product['deskripsi'],
            ]);

            if ($model->isDirty()) {
                $model->save();
            }
        }
    }
}
