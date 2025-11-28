<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Intel',
            'AMD',
            'ASUS',
            'MSI',
            'Corsair',
            'Samsung',
            'Seagate',
            'NVIDIA',
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['nama_brand' => $brand],
                [
                    'slug' => Str::slug($brand),
                    'is_active' => true,
                ]
            );
        }
    }
}
