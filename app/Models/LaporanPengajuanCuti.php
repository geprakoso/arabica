<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class LaporanPengajuanCuti extends Model
{
    protected $fillable = [
        'libur_cuti_id',
        'user_id',
        'status_pengajuan',
    ];

    protected $casts = [
        'status_pengajuan' => StatusPengajuan::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $laporan): void {
            $laporan->status_pengajuan ??= StatusPengajuan::Pending;
            $laporan->user_id ??= Auth::id();
        });
    }

    public function liburCuti(): BelongsTo
    {
        return $this->belongsTo(LiburCuti::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAs(StatusPengajuan $status): void
    {
        $this->status_pengajuan = $status;
        $this->save();

        if ($this->liburCuti) {
            $this->liburCuti->update([
                'status_pengajuan' => $status,
                'keterangan' => $status === StatusPengajuan::Diterima ? 'Disetujui oleh HR' : 'Ditolak oleh HR',
            ]);
        }
    }

    public function approveSubmission(): void
    {
        $this->markAs(StatusPengajuan::Diterima);
    }

    public function rejectSubmission(): void
    {
        $this->markAs(StatusPengajuan::Ditolak);
    }
}
