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
            $produk->sku ??= $produk->generateSmartSku();
        });
    }

    public function generateSmartSku(): string
    {
        // Ensure relations are loaded or available
        $kategori = $this->kategori;
        $brand = $this->brand;

        // Fallback to legacy/default generation if data missing
        if (! $kategori || ! $brand) {
            return $this->generateDefaultSku();
        }

        // On-the-fly backfill if codes are missing
        if (blank($kategori->kode)) {
            $kategori->kode = Kategori::generateKode($kategori->nama_kategori);
            $kategori->saveQuietly();
        }
        if (blank($brand->kode)) {
            $brand->kode = Brand::generateKode($brand->nama_brand);
            $brand->saveQuietly();
        }

        $prefix = $kategori->kode . $brand->kode;
        $prefixLen = strlen($prefix);

        // Get max sequence for this prefix
        $maxNum = self::where('sku', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(sku, ?) AS UNSIGNED)) as max_num", [$prefixLen + 1])
            ->value('max_num') ?? 0;
            
        return $prefix . ($maxNum + 1);
    }

    public static function generateDefaultSku(): string
    {
        $lastNumber = self::where('sku', 'like', 'MD%')
            ->selectRaw('MAX(CAST(SUBSTRING(sku, 4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return 'MDP' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    // Alias for backward compatibility if needed, or for default value
    public static function generateSku(): string
    {
        return self::generateDefaultSku();
    }

    public static function calculateSmartSku($kategoriId, $brandId): ?string
    {
        if (! $kategoriId || ! $brandId) {
            return null;
        }
        
        $kategori = Kategori::find($kategoriId);
        $brand = Brand::find($brandId);
        
        if (! $kategori || ! $brand) {
            return null;
        }

        // On-the-fly backfill
        if (blank($kategori->kode)) {
            $kategori->kode = Kategori::generateKode($kategori->nama_kategori);
            $kategori->saveQuietly();
        }
        if (blank($brand->kode)) {
            $brand->kode = Brand::generateKode($brand->nama_brand);
            $brand->saveQuietly();
        }

        $prefix = $kategori->kode . $brand->kode;
        $prefixLen = strlen($prefix);

        $maxNum = self::where('sku', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTRING(sku, ?) AS UNSIGNED)) as max_num", [$prefixLen + 1])
            ->value('max_num') ?? 0;

        return $prefix . ($maxNum + 1);
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
