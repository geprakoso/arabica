<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TukarTambah extends Model
{
    use HasFactory;

    protected $table = 'tb_tukar_tambah';
    protected $primaryKey = 'id_tukar_tambah';

    protected $fillable = [
        'no_nota',
        'tanggal',
        'catatan',
        'id_karyawan',
        'penjualan_id',
        'pembelian_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (TukarTambah $tukarTambah): void {
            if (blank($tukarTambah->no_nota)) {
                $tukarTambah->no_nota = self::generateNoNota();
            }
        });
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id', 'id_penjualan');
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'id_pembelian');
    }

    public function getKodeAttribute(): string
    {
        return 'TT-' . str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT);
    }

    public static function generateNoNota(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'TT-' . $date . '-';

        $latest = static::where('no_nota', 'like', $prefix . '%')
            ->orderBy('no_nota', 'desc')
            ->first();

        $next = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest->no_nota, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
