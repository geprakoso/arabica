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
        'is_active',
        'diubah_oleh_id',
        'logo_url',
    ];

    protected $casts = [
        'is active' => 'bool',
    ];

    protected static function booted()
    {
        static::saving(function (Brand $brand) {
            if (blank($brand->slug) && filled($brand->nama_brand)) {
                $brand->slug = Str::slug($brand->nama_brand);
            }
        });
    }

    public function produk()
    {
        return $this->hasMany(Produk::class);
    }
}
