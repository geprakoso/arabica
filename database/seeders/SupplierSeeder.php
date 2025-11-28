<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierAgent;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'nama_supplier' => 'PT Sinar Komputindo',
                'email' => 'halo@sinar-komputindo.co.id',
                'no_hp' => '081298888888',
                'alamat' => 'Jl. Gatot Subroto No. 10, Jakarta',
                'provinsi' => 'DKI Jakarta',
                'kota' => 'Jakarta Selatan',
                'kecamatan' => 'Setiabudi',
                'agents' => [
                    [
                        'nama_agen' => 'Budi Santoso',
                        'no_hp_agen' => '081277776666',
                    ],
                ],
            ],
            [
                'nama_supplier' => 'CV Mekar Jaya Parts',
                'email' => 'cs@mekarjaya.id',
                'no_hp' => '081255557777',
                'alamat' => 'Jl. Raya Darmo No. 3, Surabaya',
                'provinsi' => 'Jawa Timur',
                'kota' => 'Surabaya',
                'kecamatan' => 'Gubeng',
                'agents' => [
                    [
                        'nama_agen' => 'Sari Puspita',
                        'no_hp_agen' => '081233332222',
                    ],
                ],
            ],
        ];

        foreach ($suppliers as $supplierData) {
            $agents = $supplierData['agents'] ?? [];
            unset($supplierData['agents']);

            $supplier = Supplier::updateOrCreate(
                ['no_hp' => $supplierData['no_hp']],
                $supplierData
            );

            foreach ($agents as $agent) {
                SupplierAgent::updateOrCreate(
                    [
                        'supplier_id' => $supplier->id,
                        'nama_agen' => $agent['nama_agen'],
                    ],
                    [
                        'no_hp_agen' => $agent['no_hp_agen'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
