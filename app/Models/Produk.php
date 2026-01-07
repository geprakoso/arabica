<?php

namespace App\Models;

use App\Models\User;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use Illuminate\Database\Eloquent\Model;


class Produk extends Model
{
    //
    protected $table = 'md_produk';

    protected $fillable = [
        'nama_produk',
        'kategori_id',
        'brand_id',
        'sku',
        'sn',
        'garansi',
        'berat',
        'panjang',
        'lebar',
        'tinggi',
        'deskripsi',
        'diubah_oleh_id',
        'image_url',
    ];

    protected $casts = [
        'berat' => 'decimal:2',
        'panjang' => 'decimal:2',
        'lebar' => 'decimal:2',
        'tinggi' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function (Produk $produk) {
            $produk->sku ??= self::generateSku();
        });
    }

    public static function generateSku(): string
    {
        $lastNumber = self::where('sku', 'like', 'MD%')
            ->selectRaw('MAX(CAST(SUBSTRING(sku, 4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return 'MDP' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class);
    }

    public function pembelianItems()
    {
        return $this->hasMany(PembelianItem::class, PembelianItem::productForeignKey());
    }

    public function penjualanItems()
    {
        return $this->hasMany(PenjualanItem::class, 'id_produk');
    }
}
