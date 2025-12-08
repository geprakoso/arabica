<?php

namespace App\Models;

use App\Models\AkunTransaksi;
use App\Models\KodeAkun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisAkun extends Model
{
    protected $fillable = [
        'kode_akun_id',
        'kode_jenis_akun',
        'nama_jenis_akun',
    ];

    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class);
    }

    public function akunTransaksi(): BelongsTo
    {
        return $this->belongsTo(AkunTransaksi::class);
    }

    protected static function booted(): void
    {
        static::creating(function (JenisAkun $jenisAkun) {
            if (! $jenisAkun->kode_jenis_akun) {
                $jenisAkun->kode_jenis_akun = static::generateKodeJenisAkun($jenisAkun->kode_akun_id);
            }
        });
    }

    public static function generateKodeJenisAkun(?int $kodeAkunId): string
    {
        $prefix = KodeAkun::find($kodeAkunId)?->kode_akun ?? 'KODE';

        $countForPrefix = static::where('kode_akun_id', $kodeAkunId)->count();
        $next = $countForPrefix + 1;

        return sprintf('%s%02d', $prefix, $next);
    }
}
