<?php

namespace App\Models;

use App\Enums\KelompokNeraca;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KodeAkun extends Model
{
    protected $table = 'ak_kode_akuns';
    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori_akun',
        'kelompok_neraca',
    ];

    protected $casts = [
        'kelompok_neraca' => KelompokNeraca::class,
    ];

    /**
     * Jenis akun turunan dari kode akun ini.
     */
    public function jenisAkuns(): HasMany
    {
        return $this->hasMany(JenisAkun::class);
    }

    /**
     * Transaksi toko yang menggunakan jenis akun dari kode akun ini.
     */
    public function inputTransaksiTokos(): HasManyThrough
    {
        return $this->hasManyThrough(
            InputTransaksiToko::class,
            JenisAkun::class,
            'kode_akun_id',
            'kode_jenis_akun_id'
        );
    }
}
