<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjualanJasa extends Model
{
    use HasFactory;

    protected $table = 'tb_penjualan_jasa';

    protected $primaryKey = 'id_penjualan_jasa';

    protected $fillable = [
        'id_penjualan',
        'jasa_id',
        'qty',
        'harga',
        'catatan',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga' => 'decimal:2',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function jasa()
    {
        return $this->belongsTo(Jasa::class, 'jasa_id');
    }
}
