<?php

namespace App\Models;

use App\Enums\KelompokNeraca;
use Illuminate\Database\Eloquent\Model;

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
}
