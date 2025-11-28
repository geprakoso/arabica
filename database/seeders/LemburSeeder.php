<?php

namespace Database\Seeders;

use App\Enums\StatusPengajuan;
use App\Models\Lembur;
use App\Models\User;
use Illuminate\Database\Seeder;

class LemburSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'galih@example.com')->first();

        if (! $user) {
            return;
        }

        Lembur::updateOrCreate(
            [
                'user_id' => $user->id,
                'tanggal' => now()->subDays(2)->toDateString(),
                'jam_mulai' => '19:00',
            ],
            [
                'jam_selesai' => '21:00',
                'keperluan' => 'Monitoring deploy',
                'status' => StatusPengajuan::Pending,
                'catatan' => 'Data seed',
            ]
        );
    }
}
