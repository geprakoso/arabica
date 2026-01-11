<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gudang extends Model
{
    //
    use HasFactory;
    protected $table = 'md_gudang';
    protected $fillable = [
        'nama_gudang',
        'lokasi_gudang',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'latitude',
        'longitude',
        'radius_km',
        'is_active',
    ];
}
