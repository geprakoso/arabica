<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeAkun extends Model
{
    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'kategori_akun',
    ];
}
