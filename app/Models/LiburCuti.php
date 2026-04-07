<?php

namespace App\Models;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\LaporanPengajuanCuti;

class LiburCuti extends Model
{
    protected $table = 'at_libur_cuti';

    protected $fillable = [
        'user_id',
        'keperluan',
        'mulai_tanggal',
        'sampai_tanggal',
        'status_pengajuan',
        'keterangan',
    ];

    protected $casts = [
        'keperluan' => Keperluan::class,
        'status_pengajuan' => StatusPengajuan::class,
        'mulai_tanggal' => 'date',
        'sampai_tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $liburCuti): void {
            $liburCuti->status_pengajuan ??= StatusPengajuan::Pending;
        });

        static::created(function (self $liburCuti): void {
            LaporanPengajuanCuti::firstOrCreate(
                ['libur_cuti_id' => $liburCuti->id],
                [
                    'user_id' => $liburCuti->user_id,
                    'status_pengajuan' => $liburCuti->status_pengajuan,
                ],
            );
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
