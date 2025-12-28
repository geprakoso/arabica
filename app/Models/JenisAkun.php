<?php

namespace App\Models;

use App\Models\AkunTransaksi;
use App\Models\KodeAkun;
use App\Models\InputTransaksiToko;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisAkun extends Model
{
    /**
     * Nama tabel yang dipakai model.
     *
     * @var string
     */
    protected $table = 'ak_jenis_akuns';

    /**
     * Kolom yang bisa diisi secara mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_akun_id',
        'kode_jenis_akun',
        'nama_jenis_akun',
    ];

    /**
     * Relasi ke kode akun induk.
     */
    public function kodeAkun(): BelongsTo
    {
        return $this->belongsTo(KodeAkun::class);
    }

    /**
     * Relasi ke akun transaksi yang menggunakan jenis akun ini.
     */
    public function akunTransaksi(): BelongsTo
    {
        return $this->belongsTo(AkunTransaksi::class);
    }

    /**
     * Transaksi toko yang memakai jenis akun ini.
     */
    public function inputTransaksiTokos(): HasMany
    {
        return $this->hasMany(InputTransaksiToko::class, 'kode_jenis_akun_id');
    }

    /**
     * Hook lifecycle untuk mengisi kode_jenis_akun otomatis saat record dibuat.
     */
    protected static function booted(): void
    {
        // Jalankan logika setiap kali membuat record baru.
        static::creating(function (JenisAkun $jenisAkun) {
            // Bila kode belum diisi, generate berdasarkan kode_akun_id terkait.
            if (! $jenisAkun->kode_jenis_akun) {
                // Set kode unik berawalan kode akun dengan urutan berformat 2 digit.
                $jenisAkun->kode_jenis_akun = static::generateKodeJenisAkun($jenisAkun->kode_akun_id);
            }
        });
    }

    /**
     * Membentuk kode_jenis_akun unik dengan prefix kode akun dan nomor urut 2 digit.
     */
    public static function generateKodeJenisAkun(?int $kodeAkunId): string
    {
        // Ambil prefix dari KodeAkun terkait; fallback ke "KODE" jika tidak ditemukan.
        $prefix = KodeAkun::find($kodeAkunId)?->kode_akun ?? 'KODE';

        // Hitung jumlah jenis akun yang sudah memakai kode_akun_id ini untuk menentukan urutan berikutnya.
        $countForPrefix = static::where('kode_akun_id', $kodeAkunId)->count();
        // Urutan baru adalah jumlah yang ada ditambah satu.
        $next = $countForPrefix + 1;

        // Susun kode akhir dengan format PREFIX + dua digit urutan (misal: AK01).
        return sprintf('%s%02d', $prefix, $next);
    }
}
