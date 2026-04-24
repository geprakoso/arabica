<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * R01, R14, R17: Stock Batch Management
 * - Sistem batch untuk tracking stok
 * - Qty tetap (R14), qty_available berkurang saat penjualan
 * - Pessimistic locking untuk mencegah race condition
 */
class StockBatch extends Model
{
    use HasFactory;

    protected $table = 'stock_batches';

    protected $fillable = [
        'pembelian_item_id',
        'produk_id',
        'qty_total',
        'qty_available',
        'locked_at',
    ];

    protected $casts = [
        'qty_total' => 'integer',
        'qty_available' => 'integer',
        'locked_at' => 'datetime',
    ];

    /**
     * R17: Pessimistic Locking untuk pengurangan stok
     * Mencegah race condition saat multiple penjualan bersamaan
     *
     * @param int $batchId ID batch yang akan dikurangi
     * @param int $qty Jumlah yang akan dikurangi
     * @return bool True jika berhasil
     * @throws \Exception Jika stok tidak cukup atau batch tidak ditemukan
     */
    public static function decrementWithLock(int $batchId, int $qty): bool
    {
        return DB::transaction(function () use ($batchId, $qty) {
            // 🔒 LOCK record sampai transaksi selesai
            $batch = self::lockForUpdate()->find($batchId);

            if (! $batch) {
                throw new \Exception('Batch tidak ditemukan');
            }

            // Validasi stok cukup
            if ($batch->qty_available < $qty) {
                throw new \Exception(
                    "Stok tidak cukup. Tersedia: {$batch->qty_available}, Dibutuhkan: {$qty}"
                );
            }

            // Kurangi stok dan update locked_at untuk tracking
            $batch->decrement('qty_available', $qty);
            $batch->update(['locked_at' => now()]);

            return true;
        }, 5); // Retry 5x jika deadlock
    }

    /**
     * R17: Multiple batch decrement dengan locking
     * Untuk penjualan dengan multiple items
     *
     * @param array $items Array ['batch_id' => qty, ...]
     * @return bool
     * @throws \Exception
     */
    public static function decrementMultiple(array $items): bool
    {
        return DB::transaction(function () use ($items) {
            foreach ($items as $batchId => $qty) {
                self::decrementWithLock($batchId, $qty);
            }

            return true;
        });
    }

    /**
     * R14: Get qty tetap (tidak berubah)
     */
    public function getQtyTetapAttribute(): int
    {
        return $this->qty_total;
    }

    /**
     * R14: Get qty tersedia (berkurang saat penjualan)
     */
    public function getQtyTersediaAttribute(): int
    {
        return $this->qty_available;
    }

    /**
     * Relasi ke PembelianItem
     */
    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'pembelian_item_id', 'id_pembelian_item');
    }

    /**
     * Relasi ke Produk
     */
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
