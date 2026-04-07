<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GajiKaryawan extends Model
{
    protected $fillable = [
        'karyawan_id',
        'tanggal_pemberian',
        'penerimaan',
        'potongan',
        'total_penerimaan',
        'total_potongan',
        'gaji_bersih',
    ];

    protected $casts = [
        'penerimaan' => 'array',
        'potongan' => 'array',
        'tanggal_pemberian' => 'date',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            $penerimaan = collect($model->penerimaan ?? []);
            $potongan = collect($model->potongan ?? []);

            $model->total_penerimaan = $penerimaan->sum(fn ($i) => (float) ($i['nominal'] ?? 0));
            $model->total_potongan = $potongan->sum(fn ($i) => (float) ($i['nominal'] ?? 0));
            $model->gaji_bersih = $model->total_penerimaan - $model->total_potongan;
        });

        static::saved(function ($model) {
            self::syncPeriod($model->tanggal_pemberian);
        });

        static::deleted(function ($model) {
            self::syncPeriod($model->tanggal_pemberian);
        });
    }

    public static function syncPeriod($date)
    {
        // 1. Tentukan Periode (Bulan & Tahun)
        // Pastikan date adalah Carbon instance
        $date = \Carbon\Carbon::parse($date);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 2. Hitung Total Gaji Bersih di bulan tersebut
        $totalGaji = self::whereBetween('tanggal_pemberian', [$startOfMonth, $endOfMonth])
            ->sum('gaji_bersih');

        // 3. Cari Jenis Akun 5210 (Beban Gaji)
        $jenisAkun = \App\Models\JenisAkun::where('kode_jenis_akun', '5210')->first();

        // LOGIKA BARU: Auto-Create jika tidak ada
        if (! $jenisAkun) {
            // Cari parent KodeAkun (Prioritas: 52 -> 51 -> sembarang Beban)
            $kodeAkun = \App\Models\KodeAkun::where('kode_akun', '52')->first() 
                ?? \App\Models\KodeAkun::where('kode_akun', '51')->first()
                ?? \App\Models\KodeAkun::where('kategori_akun', \App\Enums\KategoriAkun::Beban)->first();

            if ($kodeAkun) {
                $jenisAkun = \App\Models\JenisAkun::create([
                    'kode_akun_id' => $kodeAkun->id,
                    'kode_jenis_akun' => '5210',
                    'nama_jenis_akun' => 'Beban Gaji',
                ]);
            } else {
                // Jika tidak ada KodeAkun yang cocok sama sekali, tidak bisa buat
                return;
            }
        }

        // 4. Cari atau Buat Transaksi di InputTransaksiToko
        $transaksi = \App\Models\InputTransaksiToko::where('kode_jenis_akun_id', $jenisAkun->id)
            ->whereBetween('tanggal_transaksi', [$startOfMonth, $endOfMonth])
            ->where('keterangan_transaksi', 'like', 'Gaji Karyawan%')
            ->first();

        // Jika Total 0, hapus transaksi jika ada
        if ($totalGaji <= 0) {
            if ($transaksi) {
                $transaksi->delete();
            }
            return;
        }

        // Siapkan data
        $data = [
            'tanggal_transaksi' => $endOfMonth,
            'kode_jenis_akun_id' => $jenisAkun->id,
            'kategori_transaksi' => \App\Enums\KategoriAkun::Beban,
            'nominal_transaksi' => $totalGaji,
            'keterangan_transaksi' => 'Gaji Karyawan ' . $endOfMonth->translatedFormat('F Y'),
            'user_id' => auth()->id() ?? 1, // Fallback user
        ];

        if ($transaksi) {
            $transaksi->update($data);
        } else {
            \App\Models\InputTransaksiToko::create($data);
        }
    }
}
