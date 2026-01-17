<?php

namespace Database\Seeders;

use App\Enums\StatusTugas;
use App\Models\PenjadwalanTugas;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenjadwalanTugasSeeder extends Seeder
{
    public function run(): void
    {
        $assignee = User::where('email', 'galih@example.com')->first();
        $creator = User::where('email', 'admin@example.com')->first();

        if (! $assignee || ! $creator) {
            return;
        }

        $task = PenjadwalanTugas::firstOrCreate(
            [
                'judul' => 'Follow up pengiriman',
            ],
            [
                'deskripsi' => 'Hubungi pelanggan untuk jadwal pengiriman besok.',
                'tanggal_mulai' => now(),
                'deadline' => now()->addDays(1),
                'status' => StatusTugas::Pending,
                'prioritas' => 'sedang',
                'created_by' => $creator->id,
            ]
        );

        $task->karyawan()->syncWithoutDetaching([$assignee->id]);
    }
}
