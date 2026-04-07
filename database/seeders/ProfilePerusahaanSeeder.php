<?php

namespace Database\Seeders;

use App\Models\ProfilePerusahaan;
use Illuminate\Database\Seeder;

class ProfilePerusahaanSeeder extends Seeder
{
    public function run(): void
    {
        ProfilePerusahaan::updateOrCreate(
            ['id' => 1],
            [
                'nama_perusahaan' => 'Sample company',
                'alamat_perusahaan' => 'Jl. Starlink no 377A Blok B Kec. Elon Kab. Mas',
                'email' => 'sample@company.com',
                'telepon' => '0888888888',
                'lat_perusahaan' => '-6.7813375',
                'long_perusahaan' => '110.8651549',
            ]
        );
    }
}
