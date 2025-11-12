<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\User;

class Jasa extends Model
{
    //
    use HasFactory;

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
            $jasa->slug ??= Str::slug($jasa->nama_jasa ?? Str::random(8));
            $jasa->sku ??= self::generateSku();
        });

        static::updating(function (Jasa $jasa) {
            if ($jasa->isDirty('nama_jasa')) {
                $jasa->slug = Str::slug($jasa->nama_jasa);
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
}
