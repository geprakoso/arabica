<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ContentCalendar extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'judul',
        'tanggal_publish',
        'content_pillar',
        'platform',
        'akun',
        'tipe_konten',
        'status',
        'caption',
        'hashtag',
        'catatan',
        'visual',
        'pic',
        'created_by',
    ];

    protected $casts = [
        'tanggal_publish' => 'datetime',
        'platform' => 'array',
        'akun' => 'array',
        'visual' => 'array',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments');
    }

    // Color mapping for content pillars
    public static function pillarColor(string $pillar): string
    {
        return match ($pillar) {
            'edukasi' => 'info',
            'promo' => 'danger',
            'branding' => 'warning',
            'engagement' => 'success',
            'testimoni' => 'primary',
            default => 'gray',
        };
    }

    // Color mapping for status
    public static function statusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'waiting' => 'warning',
            'scheduled' => 'info',
            'published' => 'success',
            default => 'gray',
        };
    }

    // Icon mapping for status
    public static function statusIcon(string $status): string
    {
        return match ($status) {
            'draft' => 'heroicon-o-pencil-square',
            'waiting' => 'heroicon-o-clock',
            'scheduled' => 'heroicon-o-calendar',
            'published' => 'heroicon-o-check-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }
}
