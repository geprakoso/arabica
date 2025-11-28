<?php

namespace Database\Seeders;

use App\Models\Jasa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JasaSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'nama_jasa' => 'Servis Laptop Lengkap',
                'harga' => 350000,
                'estimasi_waktu_jam' => 24,
                'deskripsi' => 'Pengecekan, pembersihan, dan optimasi software.',
                'is_active' => true,
            ],
            [
                'nama_jasa' => 'Penggantian Thermal Paste',
                'harga' => 150000,
                'estimasi_waktu_jam' => 2,
                'deskripsi' => 'Penggantian pasta termal dan pembersihan pendingin.',
                'is_active' => true,
            ],
            [
                'nama_jasa' => 'Perbaikan PSU / Kelistrikan',
                'harga' => 250000,
                'estimasi_waktu_jam' => 4,
                'deskripsi' => 'Diagnosa dan perbaikan suplai daya PC.',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            $model = Jasa::firstOrNew(['nama_jasa' => $service['nama_jasa']]);

            $model->fill([
                ...$service,
                'slug' => Str::slug($service['nama_jasa']),
            ]);

            // Pastikan SKU diisi agar kolom non-null tidak gagal saat insert
            $model->sku ??= Jasa::generateSku();

            $model->save();
        }
    }
}
