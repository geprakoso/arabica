<?php

namespace App\Models;

use App\Enums\KategoriAkun;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use App\Models\JenisAkun;
use App\Models\User;
use App\Models\AkunTransaksi;

class InputTransaksiToko extends Model
{
    protected $table = 'ak_input_transaksi_tokos';

    protected $fillable = [
        'tanggal_transaksi',
        'kode_jenis_akun_id',
        'kategori_transaksi',
        'nominal_transaksi',
        'keterangan_transaksi',
        'bukti_transaksi',
        'user_id',
        'akun_transaksi_id',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'date',
        'kategori_transaksi' => KategoriAkun::class,
        'nominal_transaksi' => 'decimal:2',
    ];

    public function jenisAkun(): BelongsTo
    {
        return $this->belongsTo(JenisAkun::class, 'kode_jenis_akun_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function akunTransaksi(): BelongsTo
    {
        return $this->belongsTo(AkunTransaksi::class);
    }
}
