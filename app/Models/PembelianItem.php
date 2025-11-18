<?php

namespace App\Models;

use App\Models\Produk;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PembelianItem extends Model
{
    use HasFactory;

    protected $table = 'tb_pembelian_item';
    protected $primaryKey = 'id_pembelian_item';

    protected $fillable = [
        'id_pembelian',
        'id_produk',
        'id_barang',
        'produk_id',
        'qty',
        'qty_masuk',
        'qty_sisa',
        'hpp',
        'harga_jual',
        'subtotal',
        'kondisi',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, self::productForeignKey());
    }

    public static function productForeignKey(): string
    {
        $table = (new static())->getTable();

        return static::resolveColumn($table, ['id_barang', 'id_produk', 'produk_id'], 'id_barang');
    }

    public static function qtyMasukColumn(): string
    {
        $table = (new static())->getTable();

        return static::resolveColumn($table, ['qty_masuk', 'qty'], 'qty_masuk');
    }

    public static function qtySisaColumn(): string
    {
        $table = (new static())->getTable();

        return static::resolveColumn($table, ['qty_sisa', 'qty'], 'qty_sisa');
    }

    protected static function resolveColumn(string $table, array $candidates, string $fallback): string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return $fallback;
    }

    public static function primaryKeyColumn(): string
    {
        $instance = new static();
        $keyName = $instance->getKeyName();

        return $keyName ?? 'id';
    }
}
