<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'tb_penjualan';

    protected $primaryKey = 'id_penjualan';

    protected $fillable = [
        'no_nota',
        'tanggal_penjualan',
        'catatan',
        'id_karyawan',
        'id_member',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Penjualan $penjualan): void {
            $penjualan->items()->get()->each->delete();
        });
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member');
    }

    public function items()
    {
        return $this->hasMany(PenjualanItem::class, 'id_penjualan', 'id_penjualan');
    }
}
