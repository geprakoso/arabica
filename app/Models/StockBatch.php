<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * R01, R14, R17: Stock Batch Management
 * - Sistem batch untuk tracking stok
 * - Qty tetap (R14), qty_available berkurang saat penjualan
 * - Pessimistic locking untuk mencegah race condition
 * - Audit trail dengan StockMutation
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

    // ============================================================
    // LIFECYCLE EVENTS
    // ============================================================

    protected static function booted(): void
    {
        // Auto-sync PembelianItem.qty_sisa setiap kali qty_available berubah
        // Ini menjamin qty_sisa selalu mirror/shadow dari qty_available,
        // tidak peduli dari mana perubahannya (decrementWithLock, incrementWithLock, atau direct update)
        static::updated(function (StockBatch $batch): void {
            if ($batch->wasChanged('qty_available')) {
                $qtySisaColumn = PembelianItem::qtySisaColumn();
                $pembelianItem = PembelianItem::find($batch->pembelian_item_id);

                if ($pembelianItem) {
                    $pembelianItem->{$qtySisaColumn} = max(0, (int) $batch->qty_available);
                    $pembelianItem->saveQuietly(); // Quiet supaya tidak trigger PembelianItemObserver → infinite loop
                }
            }
        });
    }

    // ============================================================
    // LOCKING METHODS (R17)
    // ============================================================

    /**
     * R17: Pessimistic Locking untuk pengurangan stok
     * Mencegah race condition saat multiple penjualan bersamaan
     *
     * @param int $batchId ID batch yang akan dikurangi
     * @param int $qty Jumlah yang akan dikurangi
     * @param array $context Context untuk audit trail [type, reference_type, reference_id, notes]
     * @return bool True jika berhasil
     * @throws \Exception Jika stok tidak cukup atau batch tidak ditemukan
     */
    public static function decrementWithLock(int $batchId, int $qty, array $context = []): bool
    {
        return DB::transaction(function () use ($batchId, $qty, $context) {
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

            $qtyBefore = $batch->qty_available;
            $qtyAfter = $qtyBefore - $qty;

            // Kurangi stok dan update locked_at untuk tracking
            $batch->decrement('qty_available', $qty);
            $batch->update(['locked_at' => now()]);

            // 📝 Create audit trail
            if (!empty($context)) {
                StockMutation::create([
                    'stock_batch_id' => $batchId,
                    'type' => $context['type'] ?? 'sale',
                    'qty_change' => -$qty,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'reference_type' => $context['reference_type'] ?? null,
                    'reference_id' => $context['reference_id'] ?? null,
                    'notes' => $context['notes'] ?? null,
                ]);
            }

            return true;
        }, 5); // Retry 5x jika deadlock
    }

    /**
     * R17: Pessimistic Locking untuk penambahan stok
     * Digunakan untuk: Stock Opname, Adjustment, RMA return
     *
     * @param int $batchId ID batch yang akan ditambah
     * @param int $qty Jumlah yang akan ditambah (positif)
     * @param array $context Context untuk audit trail [type, reference_type, reference_id, notes]
     * @return bool True jika berhasil
     * @throws \Exception Jika batch tidak ditemukan
     */
    public static function incrementWithLock(int $batchId, int $qty, array $context = []): bool
    {
        return DB::transaction(function () use ($batchId, $qty, $context) {
            // 🔒 LOCK record sampai transaksi selesai
            $batch = self::lockForUpdate()->find($batchId);

            if (! $batch) {
                throw new \Exception('Batch tidak ditemukan');
            }

            $qtyBefore = $batch->qty_available;
            $qtyAfter = $qtyBefore + $qty;

            // Tambah stok
            $batch->increment('qty_available', $qty);
            $batch->update(['locked_at' => now()]);

            // 📝 Create audit trail
            if (!empty($context)) {
                StockMutation::create([
                    'stock_batch_id' => $batchId,
                    'type' => $context['type'] ?? 'adjustment',
                    'qty_change' => +$qty,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'reference_type' => $context['reference_type'] ?? null,
                    'reference_id' => $context['reference_id'] ?? null,
                    'notes' => $context['notes'] ?? null,
                ]);
            }

            return true;
        }, 5); // Retry 5x jika deadlock
    }

    /**
     * R17: Multiple batch decrement dengan locking
     * Untuk penjualan dengan multiple items
     *
     * @param array $items Array ['batch_id' => qty, ...]
     * @param array $context Context untuk audit trail
     * @return bool
     * @throws \Exception
     */
    public static function decrementMultiple(array $items, array $context = []): bool
    {
        return DB::transaction(function () use ($items, $context) {
            foreach ($items as $batchId => $qty) {
                self::decrementWithLock($batchId, $qty, $context);
            }

            return true;
        });
    }

    /**
     * R17: Multiple batch increment dengan locking
     * Untuk stock opname/adjustment dengan multiple items
     *
     * @param array $items Array ['batch_id' => qty, ...]
     * @param array $context Context untuk audit trail
     * @return bool
     * @throws \Exception
     */
    public static function incrementMultiple(array $items, array $context = []): bool
    {
        return DB::transaction(function () use ($items, $context) {
            foreach ($items as $batchId => $qty) {
                self::incrementWithLock($batchId, $qty, $context);
            }

            return true;
        });
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Cek apakah batch masih ada stok
     */
    public function hasEnoughStock(int $qty = 1): bool
    {
        return $this->qty_available >= $qty;
    }

    /**
     * Cek apakah batch ini sedang dalam proses RMA
     */
    public function hasActiveRma(): bool
    {
        return $this->pembelianItem
            ->rmas()
            ->whereIn('status_garansi', [Rma::STATUS_DI_PACKING, Rma::STATUS_PROSES_KLAIM])
            ->exists();
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

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Relasi ke PembelianItem
     */
    public function pembelianItem(): BelongsTo
    {
        return $this->belongsTo(PembelianItem::class, 'pembelian_item_id', 'id_pembelian_item');
    }

    /**
     * Relasi ke Produk
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    /**
     * Relasi ke StockMutation (audit trail)
     */
    public function mutations(): HasMany
    {
        return $this->hasMany(StockMutation::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relasi ke RMAs (melalui PembelianItem)
     */
    public function rmas()
    {
        return $this->hasManyThrough(
            Rma::class,
            PembelianItem::class,
            'id_pembelian_item',
            'id_pembelian_item',
            'pembelian_item_id',
            'id_pembelian_item'
        );
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Scope untuk batch yang masih ada stok
     */
    public function scopeHasStock($query, int $minQty = 1)
    {
        return $query->where('qty_available', '>=', $minQty);
    }

    /**
     * Scope untuk batch yang tidak ada RMA aktif
     */
    public function scopeWithoutActiveRma($query)
    {
        return $query->whereDoesntHave('pembelianItem.rmas', function ($q) {
            $q->whereIn('status_garansi', [Rma::STATUS_DI_PACKING, Rma::STATUS_PROSES_KLAIM]);
        });
    }

    /**
     * Scope untuk batch yang available (ada stok + tidak RMA)
     */
    public function scopeAvailable($query)
    {
        return $query->hasStock()->withoutActiveRma();
    }
}
