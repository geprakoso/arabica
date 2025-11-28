<?php

namespace Database\Seeders;

use App\Models\Jasa;
use App\Models\Member;
use App\Models\PenjadwalanService;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenjadwalanServiceSeeder extends Seeder
{
    public function run(): void
    {
        $member = Member::first();
        $technician = User::where('email', 'galih@example.com')->first();
        $jasa = Jasa::first();

        if (! $member || ! $jasa) {
            return;
        }

        PenjadwalanService::updateOrCreate(
            ['no_resi' => 'SRV-SEED-0001'],
            [
                'member_id' => $member->id,
                'nama_perangkat' => 'Laptop Kantor',
                'kelengkapan' => 'Unit + Charger',
                'keluhan' => 'Booting lambat dan panas.',
                'catatan_teknisi' => 'Cek thermal dan upgrade SSD.',
                'status' => 'diagnosa',
                'technician_id' => $technician?->id,
                'jasa_id' => $jasa->id,
                'estimasi_selesai' => now()->addDays(2),
            ]
        );
    }
}
