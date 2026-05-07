<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

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
        'subtotal',      // R03: Subtotal (Qty × HPP)
        'kondisi',       // R02: Baru / Bekas
        // R04: serials dihapus (SN & Garansi tidak digunakan lagi)
    ];

    protected $casts = [
        // R04: serials dihapus
        'subtotal' => 'decimal:2',
        'hpp' => 'decimal:2',
        'harga_jual' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PembelianItem $item): void {
            $qty = (int) ($item->qty ?? 0);

            // R03: Auto-calculate subtotal (Qty × HPP)
            if (is_null($item->subtotal) && $qty > 0 && $item->hpp > 0) {
                $item->subtotal = $qty * $item->hpp;
            }

            // R02: Cek duplikat produk+kondisi sebelum save
            $exists = self::where('id_pembelian', $item->id_pembelian)
                ->where('id_produk', $item->id_produk)
                ->where('kondisi', $item->kondisi)
                ->exists();
            
            if ($exists) {
                // Log error untuk debugging
                \Log::warning('Duplicate produk+kondisi detected', [
                    'id_pembelian' => $item->id_pembelian,
                    'id_produk' => $item->id_produk,
                    'kondisi' => $item->kondisi,
                ]);
                
                throw ValidationException::withMessages([
                    'items' => 'GAGAL: Produk dengan kondisi yang sama sudah ada dalam pembelian ini. Silakan hapus duplikat atau ubah kondisi menjadi Baru/Bekas.'
                ]);
            }

            if ($qty < 1) {
                return;
            }

            $qtyMasukColumn = self::qtyMasukColumn();
            $qtySisaColumn = self::qtySisaColumn();

            if ($qtyMasukColumn !== 'qty' && is_null($item->{$qtyMasukColumn})) {
                $item->{$qtyMasukColumn} = $qty;
            }

            if ($qtySisaColumn !== 'qty' && is_null($item->{$qtySisaColumn})) {
                $item->{$qtySisaColumn} = $qty;
            }
        });

        static::saved(function (PembelianItem $item): void {
            $item->pembelian?->recalculatePaymentStatus();
            $item->pembelian?->clearCalculationCache();
        });

        // R01: Auto-create stock batch dan stock mutation saat item dibuat
        static::created(function (PembelianItem $item): void {
            if ($item->qty > 0) {
                $stockBatch = StockBatch::create([
                    'pembelian_item_id' => $item->id_pembelian_item,
                    'produk_id' => $item->id_produk ?? $item->produk_id ?? $item->id_barang,
                    'qty_total' => $item->qty,
                    'qty_available' => $item->qty_sisa ?? $item->qty_masuk ?? $item->qty,
                ]);

                $isTukarTambah = $item->pembelian?->tukarTambah()->exists();
                $mutationType = $isTukarTambah ? 'purchase_tt' : 'purchase';
                $referenceType = $isTukarTambah ? 'TukarTambah' : 'PembelianItem';
                $referenceId = $isTukarTambah
                    ? $item->pembelian?->tukarTambah?->getKey()
                    : $item->id_pembelian_item;
                $notesPrefix = $isTukarTambah ? 'Stok masuk dari Tukar Tambah' : 'Stok masuk dari pembelian';

                StockMutation::create([
                    'stock_batch_id' => $stockBatch->id,
                    'type' => $mutationType,
                    'qty_change' => $item->qty,
                    'qty_before' => 0,
                    'qty_after' => $item->qty_sisa ?? $item->qty_masuk ?? $item->qty,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'notes' => $notesPrefix . ': ' . $item->qty . ' unit',
                ]);
            }
        });

        static::updating(function (PembelianItem $item): void {
            // R03: Auto-recalculate subtotal when qty or hpp changes
            if ($item->isDirty('qty') || $item->isDirty('hpp')) {
                $qty = (int) ($item->qty ?? 0);
                $hpp = (int) ($item->hpp ?? 0);
                if ($qty > 0 && $hpp > 0) {
                    $item->subtotal = $qty * $hpp;
                }
            }

            if (! $item->isDirty('qty')) {
                return;
            }

            $qtyMasukColumn = self::qtyMasukColumn();
            $qtySisaColumn = self::qtySisaColumn();
            $qtyMasuk = (int) ($item->{$qtyMasukColumn} ?? $item->qty);
            $qtySisa = (int) ($item->{$qtySisaColumn} ?? $item->qty);

            $hasSales = $qtySisa < $qtyMasuk || $item->penjualanItems()->exists();

            if ($hasSales) {
                $notaList = $item->penjualanItems()
                    ->with('penjualan:id_penjualan,no_nota')
                    ->get()
                    ->pluck('penjualan.no_nota')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                $suffix = $notaList ? ' No nota: '.$notaList.'.' : '';
                $errorMessage = 'Qty pembelian tidak bisa diubah karena sudah ada penjualan.'.$suffix;

                // Log ke validation_logs
                \App\Models\ValidationLog::log([
                    'source_type' => 'Pembelian',
                    'source_action' => 'update',
                    'validation_type' => 'business_rule',
                    'field_name' => 'qty',
                    'error_message' => $errorMessage,
                    'error_code' => 'BUSINESS_RULE_QTY_LOCKED',
                    'input_data' => [
                        'pembelian_item_id' => $item->getKey(),
                        'pembelian_id' => $item->id_pembelian,
                        'produk_id' => $item->id_produk,
                        'qty_old' => $item->getOriginal('qty'),
                        'qty_new' => $item->qty,
                        'qty_masuk' => $qtyMasuk,
                        'qty_sisa' => $qtySisa,
                        'external_notas' => $notaList ? explode(', ', $notaList) : [],
                    ],
                    'severity' => 'warning',
                ]);

                throw ValidationException::withMessages([
                    'qty' => $errorMessage,
                ]);
            }
        });

        static::deleting(function (PembelianItem $item): void {
            if (! $item->penjualanItems()->exists()) {
                return;
            }

            $notaList = $item->penjualanItems()
                ->with('penjualan:id_penjualan,no_nota')
                ->get()
                ->pluck('penjualan.no_nota')
                ->filter()
                ->unique()
                ->implode(', ');

            $suffix = $notaList ? ' No nota: '.$notaList.'.' : '';
            $errorMessage = 'Item pembelian tidak bisa dihapus karena sudah ada penjualan.'.$suffix;

            // Log ke validation_logs
            \App\Models\ValidationLog::log([
                'source_type' => 'Pembelian',
                'source_action' => 'delete',
                'validation_type' => 'business_rule',
                'field_name' => 'id_pembelian_item',
                'error_message' => $errorMessage,
                'error_code' => 'BUSINESS_RULE_DELETE_BLOCKED',
                'input_data' => [
                    'pembelian_item_id' => $item->getKey(),
                    'pembelian_id' => $item->id_pembelian,
                    'produk_id' => $item->id_produk,
                    'external_notas' => $notaList ? explode(', ', $notaList) : [],
                ],
                'severity' => 'warning',
            ]);

            throw ValidationException::withMessages([
                'id_pembelian_item' => $errorMessage,
            ]);
        });

        static::saved(function (PembelianItem $item): void {
            $item->pembelian?->recalculatePaymentStatus();
            $item->pembelian?->clearCalculationCache();  // ✅ Clear cache saat item berubah
        });

        static::deleted(function (PembelianItem $item): void {
            $stockBatch = StockBatch::where('pembelian_item_id', $item->getKey())->first();
            if ($stockBatch) {
                StockMutation::where('stock_batch_id', $stockBatch->id)->delete();
                $stockBatch->delete();
            }

            $item->pembelian?->recalculatePaymentStatus();
            $item->pembelian?->clearCalculationCache();
        });
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'id_pembelian', 'id_pembelian');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, self::productForeignKey())->withTrashed();
    }

    public function penjualanItems()
    {
        return $this->hasMany(PenjualanItem::class, 'id_pembelian_item', 'id_pembelian_item');
    }

    public function rmas()
    {
        return $this->hasMany(Rma::class, 'id_pembelian_item', 'id_pembelian_item');
    }

    /**
     * R01: Relasi ke StockBatch
     */
    public function stockBatch()
    {
        return $this->hasOne(StockBatch::class, 'pembelian_item_id', 'id_pembelian_item');
    }

    public static function productForeignKey(): string
    {
        $table = (new static)->getTable();

        return static::resolveColumn($table, ['id_barang', 'id_produk', 'produk_id'], 'id_barang');
    }

    public static function qtyMasukColumn(): string
    {
        $table = (new static)->getTable();

        return static::resolveColumn($table, ['qty_masuk', 'qty'], 'qty_masuk');
    }

    public static function qtySisaColumn(): string
    {
        $table = (new static)->getTable();

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
        $instance = new static;
        $keyName = $instance->getKeyName();

        return $keyName ?? 'id';
    }
}
