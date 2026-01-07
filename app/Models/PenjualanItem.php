<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
            self::applyStockMutation($item->id_pembelian_item, -1 * (int) $item->qty);
            self::recalculatePenjualanTotals($item);
        });

        static::updated(function (PenjualanItem $item): void {
            $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
            $originalQty = (int) $item->getOriginal('qty');

            if ($originalBatchId) {
                self::applyStockMutation($originalBatchId, $originalQty);
            }

            self::applyStockMutation($item->id_pembelian_item, -1 * (int) $item->qty);
            self::recalculatePenjualanTotals($item);
        });

        static::deleted(function (PenjualanItem $item): void {
            $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
            $originalQty = (int) $item->getOriginal('qty');

            self::applyStockMutation($originalBatchId, $originalQty);
            self::recalculatePenjualanTotals($item);
        });
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'id_produk');
    }

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'id_pembelian_item', 'id_pembelian_item');
    }

    protected static function assertStockAvailable(PenjualanItem $item, bool $isUpdate = false): void
    {
        $batchId = $item->id_pembelian_item;
        $qty = (int) $item->qty;

        if (! $batchId || $qty < 1) {
            return;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();

        $batch = PembelianItem::query()->find($batchId);

        if (! $batch) {
            throw ValidationException::withMessages([
                'id_pembelian_item' => 'Batch pembelian tidak ditemukan.',
            ]);
        }

        $availableQty = (int) ($batch->{$qtyColumn} ?? 0);

        if ($isUpdate && $batchId === (int) $item->getOriginal('id_pembelian_item')) {
            $availableQty += (int) $item->getOriginal('qty');
        }

        if ($qty > $availableQty) {
            throw ValidationException::withMessages([
                'qty' => 'Qty melebihi stok batch yang tersedia.',
            ]);
        }
    }

    protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
    {
        if (! $batchId || $qtyDelta === 0) {
            return;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();

        $batch = PembelianItem::query()->find($batchId);

        if (! $batch) {
            return;
        }

        $updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
        $batch->{$qtyColumn} = $updatedQty;
        $batch->save();
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
    }
}
