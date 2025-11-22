<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id',
        'produk_id',
        'pembelian_item_id',
        'stok_sistem',
        'stok_fisik',
        'selisih',
        'catatan',
    ];

    protected static function booted(): void
    {
        static::saving(function (StockOpnameItem $item): void {
            $item->selisih = (int) $item->stok_fisik - (int) $item->stok_sistem;
        });
    }

    public function opname()
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'pembelian_item_id', 'id_pembelian_item');
    }

    public function applyToBatch(): void
    {
        $batch = $this->pembelianItem;

        if (! $batch) {
            return;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $batch->{$qtyColumn} = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $this->selisih);
        $batch->save();
    }
}
