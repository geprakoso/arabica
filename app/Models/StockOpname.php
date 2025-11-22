<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'tanggal',
        'status',
        'gudang_id',
        'user_id',
        'posted_by_id',
        'posted_at',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockOpname $opname): void {
            $opname->kode ??= self::generateKode();
            $opname->status ??= 'draft';
        });
    }

    public static function generateKode(): string
    {
        $prefix = 'SO-' . now()->format('Ymd') . '-';

        $latest = self::where('kode', 'like', $prefix . '%')
            ->orderByDesc('kode')
            ->first();

        $number = 1;

        if ($latest && Str::startsWith($latest->kode, $prefix)) {
            $suffix = (int) Str::after($latest->kode, $prefix);
            $number = $suffix + 1;
        }

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function post(User $user = null): void
    {
        if ($this->isPosted()) {
            return;
        }

        foreach ($this->items as $item) {
            if ($item->selisih === 0) {
                continue;
            }

            $item->applyToBatch();
        }

        $this->forceFill([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by_id' => $user?->getKey(),
        ])->save();
    }
}
