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

    /**
     * Apply selisih ke batch stok menggunakan StockBatch
     * Ini sekarang menggunakan incrementWithLock untuk atomic operation
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

        // Gunakan incrementWithLock untuk apply selisih
        // Selisih bisa positif (tambah stok) atau negatif (kurang stok)
        return StockBatch::incrementWithLock(
            $stockBatch->id,
            $this->selisih,
            [
                'type' => 'opname',
                'reference_type' => 'StockOpname',
                'reference_id' => $this->stock_opname_id,
                'notes' => "Opname: Sistem={$this->stok_sistem}, Fisik={$this->stok_fisik}, Selisih={$this->selisih}",
            ]
        );
    }
}
