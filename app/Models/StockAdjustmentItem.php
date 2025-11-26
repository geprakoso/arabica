<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'produk_id',
        'pembelian_item_id',
        'qty',
        'keterangan',
    ];

    public function adjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
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
        $batch->{$qtyColumn} = max(0, (int) ($batch->{$qtyColumn} ?? 0) + (int) $this->qty);
        $batch->save();
    }
}
