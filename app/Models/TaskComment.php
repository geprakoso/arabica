<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskComment extends Model
{
    protected $fillable = ['penjadwalan_tugas_id', 'user_id', 'body', 'attachments'];

    protected $casts = [
        'attachments' => 'array',
    ];

    /**
     * Image extensions that should be displayed as thumbnails
     */
    protected static array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PenjadwalanTugas::class, 'penjadwalan_tugas_id');
    }

    /**
     * Check if file is an image based on extension
     */
    public static function isImage(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($extension, static::$imageExtensions);
    }

    /**
     * Get icon name for document type based on extension
     */
    public static function getFileIcon(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'heroicon-o-document-text',
            'doc', 'docx' => 'heroicon-o-document',
            'xls', 'xlsx' => 'heroicon-o-table-cells',
            'txt' => 'heroicon-o-document-minus',
            'zip', 'rar', '7z' => 'heroicon-o-archive-box',
            default => 'heroicon-o-paper-clip',
        };
    }

    /**
     * Get file name from path
     */
    public static function getFileName(string $path): string
    {
        return basename($path);
    }
}
