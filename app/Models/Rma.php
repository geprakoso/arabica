<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Rma extends Model
{
    use HasFactory;

    protected $table = 'tb_rma';

    protected $primaryKey = 'id_rma';

    protected $fillable = [
        'tanggal',
        'id_pembelian_item',
        'status_garansi',
        'rma_di_mana',
        'foto_dokumen',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'foto_dokumen' => 'array',
    ];

    public const STATUS_DI_PACKING = 'di_packing';
    public const STATUS_PROSES_KLAIM = 'proses_klaim';
    public const STATUS_SELESAI = 'selesai';

    protected static function booted(): void
    {
        static::creating(function (Rma $rma): void {
            $rma->status_garansi ??= self::STATUS_DI_PACKING;

            if (! $rma->id_pembelian_item) {
                return;
            }

            if (self::hasActiveRmaForBatch((int) $rma->id_pembelian_item)) {
                throw ValidationException::withMessages([
                    'id_pembelian_item' => 'Batch ini masih dalam proses RMA aktif.',
                ]);
            }
        });

        static::updating(function (Rma $rma): void {
            $batchId = (int) ($rma->id_pembelian_item ?? 0);
            if ($batchId < 1) {
                return;
            }

            $status = $rma->status_garansi;
            $isActive = in_array($status, self::activeStatuses(), true);

            if ($isActive && self::hasActiveRmaForBatch($batchId, (int) $rma->getKey())) {
                throw ValidationException::withMessages([
                    'id_pembelian_item' => 'Batch ini masih dalam proses RMA aktif.',
                ]);
            }
        });

        // 🆕 Saat RMA selesai, kembalikan stok ke batch
        static::updated(function (Rma $rma): void {
            if ($rma->isDirty('status_garansi') && $rma->status_garansi === self::STATUS_SELESAI) {
                $rma->returnStockToInventory();
            }
        });
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_DI_PACKING, self::STATUS_PROSES_KLAIM];
    }

    public static function hasActiveRmaForBatch(int $batchId, ?int $exceptId = null): bool
    {
        $query = self::query()
            ->where('id_pembelian_item', $batchId)
            ->whereIn('status_garansi', self::activeStatuses());

        if ($exceptId) {
            $query->where($query->getModel()->getKeyName(), '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Kembalikan stok ke inventory saat RMA selesai
     * Gunakan StockBatch::incrementWithLock untuk atomic operation
     *
     * @return bool
     */
    public function returnStockToInventory(): bool
    {
        $item = $this->pembelianItem;

        if (! $item) {
            \Log::warning('RMA: Cannot return stock, pembelianItem not found', [
                'rma_id' => $this->id_rma,
            ]);
            return false;
        }

        $stockBatch = $item->stockBatch;

        if (! $stockBatch) {
            // Create StockBatch if not exists (fallback)
            $stockBatch = StockBatch::create([
                'pembelian_item_id' => $item->id_pembelian_item,
                'produk_id' => $item->id_produk ?? $item->produk_id,
                'qty_total' => $item->qty ?? 0,
                'qty_available' => (int) ($item->qty_sisa ?? $item->qty_masuk ?? $item->qty ?? 0),
            ]);
        }

        // Return 1 unit (asumsi RMA per 1 unit)
        // Jika RMA bisa multiple unit, sesuaikan qty di sini
        $returnQty = 1;

        $result = StockBatch::incrementWithLock(
            $stockBatch->id,
            $returnQty,
            [
                'type' => 'rma_return',
                'reference_type' => 'Rma',
                'reference_id' => $this->id_rma,
                'notes' => "RMA Selesai: {$this->catatan}",
            ]
        );

        \Log::info('RMA: Stock returned to inventory', [
            'rma_id' => $this->id_rma,
            'pembelian_item_id' => $item->id_pembelian_item,
            'qty_returned' => $returnQty,
            'new_stock' => $stockBatch->fresh()->qty_available,
        ]);

        return $result;
    }

    /**
     * Check if this RMA is completed
     */
    public function isCompleted(): bool
    {
        return $this->status_garansi === self::STATUS_SELESAI;
    }

    /**
     * Check if this RMA is active (in progress)
     */
    public function isActive(): bool
    {
        return in_array($this->status_garansi, self::activeStatuses(), true);
    }

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'id_pembelian_item', 'id_pembelian_item');
    }
}
