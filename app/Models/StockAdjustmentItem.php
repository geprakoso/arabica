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

    /**
     * Apply adjustment qty ke batch stok menggunakan StockBatch
     * Qty bisa positif (tambah) atau negatif (kurang)
     *
     * @return bool
     * @throws \Exception
     */
    public function applyToBatch(): bool
    {
        $batch = $this->pembelianItem;

        if (! $batch) {
            throw new \Exception('Batch pembelian tidak ditemukan untuk item ini.');
        }

        $stockBatch = $batch->stockBatch;

        if (! $stockBatch) {
            // Jika tidak ada StockBatch, buat baru (fallback untuk data lama)
            $stockBatch = StockBatch::create([
                'pembelian_item_id' => $batch->id_pembelian_item,
                'produk_id' => $batch->id_produk ?? $batch->produk_id,
                'qty_total' => $batch->qty ?? 0,
                'qty_available' => (int) ($batch->qty_sisa ?? $batch->qty_masuk ?? $batch->qty ?? 0),
            ]);
        }

        // Gunakan incrementWithLock untuk apply adjustment
        // Qty bisa positif atau negatif
        return StockBatch::incrementWithLock(
            $stockBatch->id,
            (int) $this->qty,
            [
                'type' => 'adjustment',
                'reference_type' => 'StockAdjustment',
                'reference_id' => $this->stock_adjustment_id,
                'notes' => $this->keterangan ?? "Adjustment: {$this->qty}",
            ]
        );
    }
}
