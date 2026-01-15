<?php

namespace App\Models;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $table = 'md_members';

    protected $fillable = [
        'nama_member',
        'email',
        'no_hp',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'image_url',
    ];

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_member', 'id');
    }

    public function penjualanItems()
    {
        return $this->hasManyThrough(
            PenjualanItem::class,
            Penjualan::class,
            'id_member',
            'id_penjualan',
            'id',
            'id_penjualan'
        );
    }

    public function penjualanJasa()
    {
        return $this->hasManyThrough(
            PenjualanJasa::class,
            Penjualan::class,
            'id_member',
            'id_penjualan',
            'id',
            'id_penjualan'
        );
    }
}
