<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'tb_pembelian';
    protected $primaryKey = 'id_pembelian';

    protected $fillable = [
        'no_po',
        'tanggal',
        'harga_jual',
        'catatan',
        'tipe_pembelian',
        'jenis_pembayaran',
        'tgl_tempo',
        'id_karyawan',
        'id_supplier',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pembelian $pembelian): void {
            if (blank($pembelian->no_po)) {
                $pembelian->no_po = self::generatePO();
            }
        });
    }

    public static function generatePO(): string
    {
        $prefix = 'PO-';

        $lastNumber = self::where('no_po', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(no_po, 4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'tanggal' => 'date',
        'tgl_tempo' => 'date',
        'harga_jual' => 'decimal:2',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function requestOrders()
    {
        return $this->belongsToMany(RequestOrder::class, 'pembelian_request_order', 'pembelian_id', 'request_order_id')
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(PembelianItem::class, 'id_pembelian', 'id_pembelian');
    }

    public function jasaItems()
    {
        return $this->hasMany(PembelianJasa::class, 'id_pembelian', 'id_pembelian');
    }

    public function tukarTambah()
    {
        return $this->hasOne(TukarTambah::class, 'pembelian_id', 'id_pembelian');
    }
}
