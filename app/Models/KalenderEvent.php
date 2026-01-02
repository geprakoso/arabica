<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KalenderEvent extends Model
{
    protected $fillable = [
        'judul',
        'tipe',
        'deskripsi',
        'lokasi',
        'mulai',
        'selesai',
        'all_day',
        'created_by',
    ];

    protected $casts = [
        'mulai' => 'datetime',
        'selesai' => 'datetime',
        'all_day' => 'bool',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
