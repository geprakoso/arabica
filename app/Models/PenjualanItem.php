<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanItem extends Model
{
    use HasFactory;

    protected $table = 'tb_penjualan_item';

    protected $primaryKey = 'id_penjualan_item';

    protected $fillable = [
        'id_penjualan',
        'id_produk',
        'id_pembelian_item',
        'qty',
        'hpp',
        'harga_jual',
        'kondisi',
        'serials',
    ];

    protected $casts = [
        'hpp' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'serials' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (PenjualanItem $item): void {
            self::applyBatchDefaults($item);
            self::assertStockAvailable($item);
        });

        static::updating(function (PenjualanItem $item): void {
            self::applyBatchDefaults($item);
            self::assertStockAvailable($item, true);
        });

        static::created(function (PenjualanItem $item): void {
            DB::transaction(function () use ($item) {
                self::applyStockMutation($item, -1 * (int) $item->qty, 'sale');
                self::recalculatePenjualanTotals($item);
            });
        });

        static::updated(function (PenjualanItem $item): void {
            DB::transaction(function () use ($item) {
                $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
                $originalQty = (int) $item->getOriginal('qty');
                $newBatchId = (int) $item->id_pembelian_item;
                $newQty = (int) $item->qty;

                // ✅ FIX: Skip stock mutation jika batch dan qty tidak berubah
                // Mencegah double return/deduction saat edit tanpa perubahan material
                if ($originalBatchId === $newBatchId && $originalQty === $newQty) {
                    self::recalculatePenjualanTotals($item);

                    return;
                }

                // Kembalikan stok lama
                if ($originalBatchId) {
                    $originalItem = clone $item;
                    $originalItem->id_pembelian_item = $originalBatchId;
                    $originalItem->qty = $originalQty;
                    self::applyStockMutation($originalItem, $originalQty, 'sale_return');
                }

                // Kurangi stok baru
                self::applyStockMutation($item, -1 * $newQty, 'sale');

                self::recalculatePenjualanTotals($item);
            });
        });

        static::deleted(function (PenjualanItem $item): void {
            DB::transaction(function () use ($item) {
                self::applyStockMutation($item, (int) $item->qty, 'sale_return');
                self::recalculatePenjualanTotals($item);
            });
        });
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk')->withTrashed();
    }

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'id_pembelian_item', 'id_pembelian_item');
    }

    public function stockBatch()
    {
        return $this->hasOneThrough(
            StockBatch::class,
            PembelianItem::class,
            'id_pembelian_item',
            'pembelian_item_id',
            'id_pembelian_item',
            'id_pembelian_item'
        );
    }

    protected static function assertStockAvailable(PenjualanItem $item, bool $isUpdate = false): void
    {
        $batchId = $item->id_pembelian_item;
        $qty = (int) $item->qty;

        if (! $batchId || $qty < 1) {
            return;
        }

        if (Rma::hasActiveRmaForBatch((int) $batchId)) {
            throw ValidationException::withMessages([
                'id_pembelian_item' => 'Batch ini sedang dalam proses RMA aktif.',
            ]);
        }

        // ✅ Gunakan StockBatch sebagai sumber stok
        $stockBatch = StockBatch::where('pembelian_item_id', $batchId)->first();

        if (! $stockBatch) {
            throw ValidationException::withMessages([
                'id_pembelian_item' => 'StockBatch tidak ditemukan untuk batch ini. Pastikan sudah melakukan sync stok.',
            ]);
        }

        $availableQty = $stockBatch->qty_available;

        if ($isUpdate && $batchId === (int) $item->getOriginal('id_pembelian_item')) {
            $availableQty += (int) $item->getOriginal('qty');
        }

        if ($qty > $availableQty) {
            throw ValidationException::withMessages([
                'qty' => "Qty melebihi stok batch yang tersedia. Tersedia: {$availableQty}, Diminta: {$qty}",
            ]);
        }
    }

    protected static function applyStockMutation(PenjualanItem $item, int $qtyDelta, string $mutationType = 'sale'): void
    {
        $batchId = $item->id_pembelian_item;

        if (! $batchId || $qtyDelta === 0) {
            return;
        }

        $stockBatch = StockBatch::where('pembelian_item_id', $batchId)->first();

        if (! $stockBatch) {
            throw new \RuntimeException("StockBatch tidak ditemukan untuk PembelianItem #{$batchId}. Pastikan sudah melakukan sync stok.");
        }

        if ($qtyDelta < 0) {
            StockBatch::decrementWithLock(
                $stockBatch->id,
                abs($qtyDelta),
                [
                    'type' => $mutationType,
                    'reference_type' => 'PenjualanItem',
                    'reference_id' => $item->id_penjualan_item,
                    'notes' => "Penjualan: {$item->qty} unit",
                ]
            );
        } elseif ($qtyDelta > 0) {
            StockBatch::incrementWithLock(
                $stockBatch->id,
                $qtyDelta,
                [
                    'type' => 'sale_return',
                    'reference_type' => 'PenjualanItem',
                    'reference_id' => $item->id_penjualan_item,
                    'notes' => "Return/Cancel: {$qtyDelta} unit",
                ]
            );
        }
    }

    protected static function applyBatchDefaults(PenjualanItem $item): void
    {
        if (! $item->id_pembelian_item) {
            return;
        }

        $batch = $item->relationLoaded('pembelianItem')
            ? $item->pembelianItem
            : PembelianItem::query()->find($item->id_pembelian_item);

        if (! $batch) {
            return;
        }

        // HPP selalu sinkron dengan batch agar laporan konsisten
        $item->hpp = $batch->hpp;

        // Harga jual boleh diubah manual, hanya isi default jika belum diisi
        if (is_null($item->harga_jual)) {
            $item->harga_jual = $batch->harga_jual;
        }

        // Kondisi otomatis mengikuti batch kecuali user memilih nilai khusus
        if (! $item->kondisi) {
            $item->kondisi = $batch->kondisi;
        }
    }

    protected static function recalculatePenjualanTotals(PenjualanItem $item): void
    {
        $penjualanId = (int) ($item->id_penjualan ?? 0);

        if (! $penjualanId) {
            return;
        }

        $penjualan = Penjualan::query()->find($penjualanId);

        if (! $penjualan) {
            return;
        }

        $penjualan->recalculateTotals();
        $penjualan->clearCalculationCache();
    }
}
