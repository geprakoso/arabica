<?php

namespace App\Models;

use App\Models\Produk;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Brand extends Model
{
    //
    use HasFactory;

    protected $table = 'md_brand';

    protected $fillable = [
        'nama_brand',
        'slug',
        'kode',
        'is_active',
        'diubah_oleh_id',
        'logo_url',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    protected static function booted()
    {
        static::saving(function (Brand $brand) {
            if (blank($brand->slug) && filled($brand->nama_brand)) {
                $brand->slug = Str::slug($brand->nama_brand);
            }
            if (blank($brand->kode) && filled($brand->nama_brand)) {
                $brand->kode = self::generateKode($brand->nama_brand);
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

    public function produk()
    {
        return $this->hasMany(Produk::class);
    }
}
