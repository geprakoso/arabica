<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $table = 'md_kategori';
    //
    protected $fillable = [
        'slug',
        'nama_kategori',
        'kode',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Kategori $kategori) {
            if (blank($kategori->slug) && filled($kategori->nama_kategori)) {
                $kategori->slug = Str::slug($kategori->nama_kategori);
            }
            if (blank($kategori->kode) && filled($kategori->nama_kategori)) {
                $kategori->kode = self::generateKode($kategori->nama_kategori);
            }
        });
    }

    public static function generateKode(string $nama): string
    {
        $cleanName = Str::upper(Str::slug($nama, ''));
        // 1. Try first 3 letters
        $try1 = substr($cleanName, 0, 3);
        if (strlen($try1) < 3) {
             return str_pad($try1, 3, 'X'); 
        }
        
        if (! self::where('kode', $try1)->exists()) {
            return $try1;
        }

        // 2. Try first 2 letters + 4th letter (index 2 + 1 = 3)
        // Check if length enough
        if (strlen($cleanName) >= 4) {
             $try2 = substr($cleanName, 0, 2) . substr($cleanName, 3, 1);
             if (! self::where('kode', $try2)->exists()) {
                 return $try2;
             }
        }

        // 3. Fallback: First 2 letters + find any available letter
        $prefix = substr($cleanName, 0, 2);
        for ($i = 0; $i < 10; $i++) {
            $candidate = $prefix . $i;
             if (! self::where('kode', $candidate)->exists()) {
                 return $candidate;
             }
        }
        
        // Final fallback: Random
        return Str::upper(Str::random(3));
    }

}
