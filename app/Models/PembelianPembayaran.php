<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianPembayaran extends Model
{
    protected $table = 'tb_pembelian_pembayaran';

    protected $primaryKey = 'id_pembelian_pembayaran';

    protected $fillable = [
        'id_pembelian',
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
        static::saved(function (PembelianPembayaran $payment): void {
            $payment->pembelian?->recalculatePaymentStatus();
        });

        static::deleted(function (PembelianPembayaran $payment): void {
            $payment->pembelian?->recalculatePaymentStatus();
        });
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function akunTransaksi()
    {
        return $this->belongsTo(AkunTransaksi::class, 'akun_transaksi_id');
    }
}
