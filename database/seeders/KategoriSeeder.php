<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Processor',
            'Motherboard',
            'RAM',
            'Storage',
            'Graphics Card',
            'Power Supply',
            'PC Case',
            'Cooling',
        ];

        foreach ($categories as $category) {
            Kategori::updateOrCreate(
                ['nama_kategori' => $category],
                [
                    'slug' => Str::slug($category),
                    'is_active' => true,
                ]
            );
        }
    }
}
