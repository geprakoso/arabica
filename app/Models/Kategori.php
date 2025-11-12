<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Kategori extends Model
{
    //
    protected $fillable = [
        'slug',
        'nama_kategori',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Kategori $kategori) {
            if (blank($kategori->slug) && filled($kategori->nama_kategori)) {
                $kategori->slug = Str::slug($kategori->nama_kategori);
            }
        });
    }

}
