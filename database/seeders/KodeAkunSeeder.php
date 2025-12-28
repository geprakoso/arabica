<?php

namespace Database\Seeders;

use App\Enums\KategoriAkun;
use App\Enums\KelompokNeraca;
use App\Models\KodeAkun;
use Illuminate\Database\Seeder;

class KodeAkunSeeder extends Seeder
{
    public function run(): void
    {
        $kodeAkunDefaults = [
            [
                'kode_akun' => '11',
                'nama_akun' => 'Aset Lancar',
                'kategori_akun' => KategoriAkun::Aktiva->value,
                'kelompok_neraca' => KelompokNeraca::AsetLancar,
            ],
            [
                'kode_akun' => '12',
                'nama_akun' => 'Aset Tidak Lancar',
                'kategori_akun' => KategoriAkun::Aktiva->value,
                'kelompok_neraca' => KelompokNeraca::AsetTidakLancar,
            ],
            [
                'kode_akun' => '21',
                'nama_akun' => 'Kewajiban Lancar',
                'kategori_akun' => KategoriAkun::Pasiva->value,
                'kelompok_neraca' => KelompokNeraca::LiabilitasJangkaPendek,
            ],
            [
                'kode_akun' => '22',
                'nama_akun' => 'Kewajiban Jangka Panjang',
                'kategori_akun' => KategoriAkun::Pasiva->value,
                'kelompok_neraca' => KelompokNeraca::LiabilitasJangkaPanjang,
            ],
            [
                'kode_akun' => '31',
                'nama_akun' => 'Modal',
                'kategori_akun' => KategoriAkun::Pasiva->value,
                'kelompok_neraca' => KelompokNeraca::Ekuitas,
            ],
            [
                'kode_akun' => '41',
                'nama_akun' => 'Pendapatan',
                'kategori_akun' => KategoriAkun::Pendapatan->value,
                'kelompok_neraca' => null,
            ],
            [
                'kode_akun' => '51',
                'nama_akun' => 'Harga Pokok Penjualan',
                'kategori_akun' => KategoriAkun::Beban->value,
                'kelompok_neraca' => null,
            ],
            [
                'kode_akun' => '52',
                'nama_akun' => 'Beban Operasional',
                'kategori_akun' => KategoriAkun::Beban->value,
                'kelompok_neraca' => null,
            ],
            [
                'kode_akun' => '61',
                'nama_akun' => 'Beban Administrasi',
                'kategori_akun' => KategoriAkun::Beban->value,
                'kelompok_neraca' => null,
            ],
            [
                'kode_akun' => '71',
                'nama_akun' => 'Pendapatan Non Operasional',
                'kategori_akun' => KategoriAkun::Pendapatan->value,
                'kelompok_neraca' => null,
            ],
            [
                'kode_akun' => '81',
                'nama_akun' => 'Beban Non Operasional',
                'kategori_akun' => KategoriAkun::Beban->value,
                'kelompok_neraca' => null,
            ],
        ];

        foreach ($kodeAkunDefaults as $data) {
            KodeAkun::updateOrCreate(
                ['kode_akun' => $data['kode_akun']],
                [
                    'nama_akun' => $data['nama_akun'],
                    'kategori_akun' => $data['kategori_akun'],
                    'kelompok_neraca' => $data['kelompok_neraca'],
                ]
            );
        }
    }
}
