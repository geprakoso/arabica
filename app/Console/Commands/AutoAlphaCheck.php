<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Absensi;
use Carbon\Carbon;

class AutoAlphaCheck extends Command
{
    protected $signature = 'absensi:auto-alpha';
    protected $description = 'Set Alpha untuk karyawan yang belum absen hari ini';

    public function handle()
    {
        $today = Carbon::today();

        // 1. Ambil semua user (karyawan) aktif
        // Kamu bisa filter misal where('role', 'karyawan') jika pakai role
        $users = User::all();

        $count = 0;

        foreach ($users as $user) {
            // 2. Cek apakah user ini sudah punya data absensi hari ini?
            $hasAbsensi = Absensi::where('user_id', $user->id)
                ->whereDate('tanggal', $today)
                ->exists();

            // 3. Jika tidak ada, buatkan status Alpha
            if (!$hasAbsensi) {
                Absensi::create([
                    'user_id'       => $user->id,
                    'tanggal'       => $today,
                    'jam_masuk'     => '00:00:00', // Default Value
                    'status'        => 'alpha', // sesuai enum pada kolom status
                    'keterangan'    => 'Otomatis by System (Tidak Absen)',
                ]);
                $count++;
            }
        }
        $this->info("Berhasil: {$count} karyawan ditandai Alpha.");
    }
}
