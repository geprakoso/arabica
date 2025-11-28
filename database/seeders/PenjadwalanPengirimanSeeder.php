<?php

namespace Database\Seeders;

use App\Models\PenjadwalanPengiriman;
use App\Models\Penjualan;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenjadwalanPengirimanSeeder extends Seeder
{
    public function run(): void
    {
        $penjualan = Penjualan::first();
        $driver = User::where('email', 'galih@example.com')->first();

        if (! $penjualan) {
            return;
        }

        $pengiriman = PenjadwalanPengiriman::firstOrNew(['no_resi' => 'DEL-SEED-0001']);

        $pengiriman->forceFill([
            'penjualan_id' => $penjualan->id_penjualan,
            'member_id' => $penjualan->id_member,
            'karyawan_id' => $driver?->id,
            'alamat' => $penjualan->member?->alamat ?? 'Alamat belum diisi',
            'penerima_nama' => $penjualan->member?->nama_member,
            'penerima_no_hp' => $penjualan->member?->no_hp,
            'tanggal_penerimaan' => now()->addDays(1),
            'status' => 'shipping',
            'catatan' => 'Contoh jadwal pengiriman seed.',
        ]);

        $pengiriman->save();
    }
}
