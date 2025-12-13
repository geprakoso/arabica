<?php

namespace Database\Seeders;

use App\Enums\KategoriAkun;
use App\Models\AkunTransaksi;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class InputTransaksiTokoSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada user sebagai pencatat transaksi.
        $user = User::first() ?? User::create([
            'name' => 'Admin Toko',
            'email' => 'admin-transaksi@example.com',
            'password' => Hash::make('password'),
        ]);

        // Pastikan ada akun transaksi untuk referensi.
        $akunTransaksi = AkunTransaksi::firstOrCreate(
            ['kode_akun' => 'CASH-SEED'],
            [
                'nama_akun' => 'Kas Operasional Toko',
                'jenis' => 'tunai',
                'nama_bank' => null,
                'nama_rekening' => null,
                'no_rekening' => null,
                'catatan' => 'Akun default untuk seed transaksi.',
                'is_active' => true,
            ]
        );

        // Siapkan kode akun induk untuk tiap kategori.
        $kodeAkunConfigs = [
            'OPR' => ['nama' => 'Beban Operasional Toko', 'kategori' => KategoriAkun::Beban->value],
            'LOG' => ['nama' => 'Biaya Logistik & Pengiriman', 'kategori' => KategoriAkun::Beban->value],
            'PEN' => ['nama' => 'Pendapatan Toko', 'kategori' => KategoriAkun::Pendapatan->value],
        ];

        $kodeAkunMap = [];
        foreach ($kodeAkunConfigs as $kode => $data) {
            $kodeAkunMap[$kode] = KodeAkun::updateOrCreate(
                ['kode_akun' => $kode],
                [
                    'nama_akun' => $data['nama'],
                    'kategori_akun' => $data['kategori'],
                ]
            );
        }

        // Jenis akun turunan untuk kebutuhan laporan.
        $jenisConfigs = [
            ['kode' => 'OPR01', 'nama' => 'Pembelian Stok Laptop', 'kode_akun' => 'OPR'],
            ['kode' => 'OPR02', 'nama' => 'Belanja Aksesoris & Sparepart', 'kode_akun' => 'OPR'],
            ['kode' => 'LOG01', 'nama' => 'Ongkos Kirim Ekspedisi', 'kode_akun' => 'LOG'],
            ['kode' => 'LOG02', 'nama' => 'Bahan Packing & Logistik', 'kode_akun' => 'LOG'],
            ['kode' => 'PEN01', 'nama' => 'Penjualan Laptop Rakitan', 'kode_akun' => 'PEN'],
            ['kode' => 'PEN02', 'nama' => 'Pendapatan Servis & Instalasi', 'kode_akun' => 'PEN'],
        ];

        $jenisMap = [];
        $jenisKategori = [];

        foreach ($jenisConfigs as $config) {
            $kodeAkun = $kodeAkunMap[$config['kode_akun']];

            $jenis = JenisAkun::updateOrCreate(
                ['kode_jenis_akun' => $config['kode']],
                [
                    'kode_akun_id' => $kodeAkun->id,
                    'nama_jenis_akun' => $config['nama'],
                ]
            );

            $jenisMap[$config['kode']] = $jenis;
            $jenisKategori[$config['kode']] = $kodeAkun->kategori_akun;
        }

        // Template transaksi (Bahasa Indonesia) untuk 10 entri per minggu selama 8 minggu.
        $expenseTemplates = [
            ['jenis_code' => 'OPR01', 'keterangan' => 'Belanja stok laptop kelas menengah', 'min' => 4500000, 'max' => 8500000],
            ['jenis_code' => 'OPR02', 'keterangan' => 'Belanja aksesoris & sparepart rutin', 'min' => 500000, 'max' => 1500000],
            ['jenis_code' => 'LOG01', 'keterangan' => 'Ongkos kirim dari supplier ke gudang', 'min' => 150000, 'max' => 600000],
            ['jenis_code' => 'LOG02', 'keterangan' => 'Pembelian bahan packing & bubble wrap', 'min' => 75000, 'max' => 250000],
        ];

        $incomeTemplates = [
            ['jenis_code' => 'PEN01', 'keterangan' => 'Penjualan paket laptop siap pakai', 'min' => 4500000, 'max' => 9000000],
            ['jenis_code' => 'PEN02', 'keterangan' => 'Pendapatan servis/instalasi software', 'min' => 250000, 'max' => 750000],
        ];

        $startOfRange = Carbon::now()->subWeeks(8)->startOfWeek();

        for ($week = 0; $week < 8; $week++) {
            $weekStart = $startOfRange->copy()->addWeeks($week);

            for ($i = 0; $i < 10; $i++) {
                // Dominan beban (komputer & logistik), sebagian pendapatan.
                $isExpense = $i < 6;
                $template = Arr::random($isExpense ? $expenseTemplates : $incomeTemplates);

                $jenisCode = $template['jenis_code'];
                $jenis = $jenisMap[$jenisCode];
                $kategori = $jenisKategori[$jenisCode];

                InputTransaksiToko::create([
                    'tanggal_transaksi' => $weekStart->copy()->addDays($i % 7),
                    'kode_jenis_akun_id' => $jenis->id,
                    'kategori_transaksi' => $kategori,
                    'nominal_transaksi' => random_int($template['min'], $template['max']),
                    'keterangan_transaksi' => "{$template['keterangan']} - minggu " . ($week + 1),
                    'user_id' => $user->id,
                    'akun_transaksi_id' => $akunTransaksi->id,
                    'bukti_transaksi' => null,
                ]);
            }
        }
    }
}
