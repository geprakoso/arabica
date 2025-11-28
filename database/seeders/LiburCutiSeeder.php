<?php

namespace Database\Seeders;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use App\Models\LiburCuti;
use App\Models\User;
use Illuminate\Database\Seeder;

class LiburCutiSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'huda@example.com')->first();

        if (! $user) {
            return;
        }

        LiburCuti::updateOrCreate(
            [
                'user_id' => $user->id,
                'mulai_tanggal' => now()->addDays(5)->toDateString(),
            ],
            [
                'sampai_tanggal' => now()->addDays(6)->toDateString(),
                'keperluan' => Keperluan::Cuti,
                'status_pengajuan' => StatusPengajuan::Pending,
                'keterangan' => 'Permohonan cuti (seed).',
            ]
        );
    }
}
