<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jasa extends Model
{
    //
    use HasFactory;

    protected $table = 'md_jasa';

    protected $fillable = [
        'slug',
        'nama_jasa',
        'sku',
        'harga',
        'image_url',
        'is_active',
        'estimasi_waktu_jam',
        'deskripsi',
        'diubah_oleh_id',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'is_active' => 'boolean',
        'estimasi_waktu_jam' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Jasa $jasa) {
            $jasa->slug ??= self::generateUniqueSlug($jasa->nama_jasa ?? Str::random(8));
            $jasa->sku ??= self::generateSku();
        });

        static::updating(function (Jasa $jasa) {
            if ($jasa->isDirty('nama_jasa')) {
                $jasa->slug = self::generateUniqueSlug($jasa->nama_jasa, $jasa->id);
            }
        });
    }

    public static function generateSku(): string
    {
        $lastNumber = (int) self::where('sku', 'like', 'JSA%')
            ->selectRaw('MAX(CAST(SUBSTRING(sku, 4) AS UNSIGNED)) as max_num')
            ->value('max_num');

        return 'JSA' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh_id');
    }

    public function getHargaFormattedAttribute(): string
    {
        if ($this->harga === null) {
            return '-';
        }

        return 'Rp ' . number_format((int) $this->harga, 0, ',', '.');
    }

    /**
     * Generate a slug and keep it unique by appending a counter when needed.
     */
    public static function generateUniqueSlug(string $namaJasa, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($namaJasa) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 1;

        while (
            self::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
