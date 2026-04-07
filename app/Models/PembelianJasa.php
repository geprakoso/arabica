<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianJasa extends Model
{
    use HasFactory;

    protected $table = 'tb_pembelian_jasa';

    protected $primaryKey = 'id_pembelian_jasa';

    protected $fillable = [
        'id_pembelian',
        'jasa_id',
        'qty',
        'harga',
        'catatan',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (PembelianJasa $item): void {
            $item->pembelian?->recalculatePaymentStatus();
        });

        static::deleted(function (PembelianJasa $item): void {
            $item->pembelian?->recalculatePaymentStatus();
        });
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function jasa()
    {
        return $this->belongsTo(Jasa::class, 'jasa_id');
    }
}
