<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPembayaran extends Model
{
    protected $table = 'tb_penjualan_pembayaran';

    protected $primaryKey = 'id_penjualan_pembayaran';

    protected $fillable = [
        'id_penjualan',
        'metode_bayar',
        'akun_transaksi_id',
        'jumlah',
        'catatan',
    ];

    protected $casts = [
        'jumlah' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(function (PenjualanPembayaran $payment): void {
            $payment->penjualan?->recalculatePaymentStatus();
        });

        static::deleted(function (PenjualanPembayaran $payment): void {
            $payment->penjualan?->recalculatePaymentStatus();
        });
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function akunTransaksi()
    {
        return $this->belongsTo(AkunTransaksi::class, 'akun_transaksi_id');
    }
}
