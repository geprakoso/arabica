<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeAkun extends Model
{
    protected $table = 'ak_kode_akuns';
    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori_akun',
    ];
}
