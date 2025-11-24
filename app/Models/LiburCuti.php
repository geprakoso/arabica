<?php

namespace App\Models;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
