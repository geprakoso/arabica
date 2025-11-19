<?php

namespace App\Models;

use App\Models\Kategori;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'md_kategori';
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
