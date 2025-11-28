<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\User;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'galih@example.com')->first();

        if (! $user) {
            return;
        }

        Absensi::updateOrCreate(
            [
                'user_id' => $user->id,
                'tanggal' => now()->subDay()->toDateString(),
            ],
            [
                'jam_masuk' => '08:10',
                'jam_keluar' => '17:05',
                'status' => 'hadir',
                'keterangan' => 'Data seed',
                'lat_absen' => -6.2,
                'long_absen' => 106.82,
                'camera_test' => null,
            ]
        );
    }
}
