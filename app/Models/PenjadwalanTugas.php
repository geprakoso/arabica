<?php

namespace App\Models;

use App\Enums\StatusTugas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjadwalanTugas extends Model
{
    protected $fillable = [
        'karyawan_id',
        'created_by',
        'judul',
        'deskripsi',
        'tanggal_mulai',
        'deadline',
        'status',
        'prioritas',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'deadline' => 'date',
        'status' => StatusTugas::class,
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
