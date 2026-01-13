<?php

namespace App\Models;

use App\Enums\StatusTugas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjadwalanTugas extends Model
{
    protected $fillable = [
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

    public function karyawan(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'penjadwalan_tugas_user');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function views(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskView::class);
    }

    public function latestComment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TaskComment::class)->latestOfMany();
    }

    public function currentUserView(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TaskView::class)->where('user_id', auth()->id());
    }
}
