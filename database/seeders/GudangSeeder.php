<?php

namespace Database\Seeders;

use App\Models\Gudang;
use Illuminate\Database\Seeder;

class GudangSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'nama_gudang' => 'Gudang Pusat',
                'lokasi_gudang' => 'Kawasan Industri Delta, Blok A1',
            ],
            [
                'nama_gudang' => 'Gudang Cabang Surabaya',
                'lokasi_gudang' => 'Jl. Kertajaya No. 55',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Gudang::updateOrCreate(
                ['nama_gudang' => $warehouse['nama_gudang']],
                [
                    ...$warehouse,
                    'is_active' => true,
                ]
            );
        }
    }
}
