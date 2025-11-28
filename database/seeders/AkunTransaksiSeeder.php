<?php

namespace Database\Seeders;

use App\Models\AkunTransaksi;
use Illuminate\Database\Seeder;

class AkunTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'nama_akun' => 'Bank Transfer Operasional',
                'kode_akun' => 'BNI-VA-001',
                'jenis' => 'transfer',
                'nama_bank' => 'BNI',
                'nama_rekening' => 'CV Arabica Tech',
                'no_rekening' => '880011112222',
                'catatan' => 'Rekening utama untuk pembayaran supplier.',
                'is_active' => true,
            ],
            [
                'nama_akun' => 'Kas Toko',
                'kode_akun' => 'CASH-001',
                'jenis' => 'tunai',
                'nama_bank' => null,
                'nama_rekening' => null,
                'no_rekening' => null,
                'catatan' => 'Kas fisik untuk transaksi offline.',
                'is_active' => true,
            ],
            [
                'nama_akun' => 'E-Wallet Operasional',
                'kode_akun' => 'EW-OVO-01',
                'jenis' => 'e-wallet',
                'nama_bank' => 'OVO',
                'nama_rekening' => 'Arabica Digital',
                'no_rekening' => '088812341234',
                'catatan' => 'E-wallet untuk pembayaran cepat.',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            AkunTransaksi::updateOrCreate(
                ['kode_akun' => $account['kode_akun']],
                $account
            );
        }
    }
}
