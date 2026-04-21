<?php

namespace App\Models;

use App\Enums\MetodeBayar;
use App\Support\CacheHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'tb_penjualan';

    protected $primaryKey = 'id_penjualan';

    // Flag to allow TukarTambah cascade deletion
    public static bool $allowTukarTambahDeletion = false;

    protected $fillable = [
        'no_nota',
        'tanggal_penjualan',
        'catatan',
        'id_karyawan',
        'id_member',
        'total',
        'diskon_total',
        'grand_total',
        'metode_bayar',
        'akun_transaksi_id',
        'tunai_diterima',
        'kembalian',
        'status_pembayaran',
        'gudang_id',
        'sumber_transaksi',
        'status_dokumen',
        'is_nerfed',
        'foto_dokumen',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
        'metode_bayar' => MetodeBayar::class,
        'is_nerfed' => 'boolean',
        'foto_dokumen' => 'array',
    ];

    public static function generateNoNota(string $prefixCode = 'PJ'): string
    {
        return DB::transaction(function () use ($prefixCode) {
            $date = now()->format('Ym');
            $prefix = $prefixCode.'-'.$date.'-';

            $latest = static::where('no_nota', 'like', $prefix.'%')
                ->orderBy('no_nota', 'desc')
                ->lockForUpdate()
                ->first();

            $next = 1;
            if ($latest && preg_match('/'.preg_quote($prefix).'(\d+)$/', $latest->no_nota, $m)) {
                $next = (int) $m[1] + 1;
            }

            return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function akunTransaksi()
    {
        return $this->belongsTo(AkunTransaksi::class, 'akun_transaksi_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(PenjualanPembayaran::class, 'id_penjualan', 'id_penjualan');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member');
    }

    public function items()
    {
        return $this->hasMany(PenjualanItem::class, 'id_penjualan', 'id_penjualan');
    }

    public function recalculateTotals(): void
    {
        $barangTotal = (float) ($this->items()
            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
            ->value('total') ?? 0);

        $jasaTotal = (float) ($this->jasaItems()
            ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
            ->value('total') ?? 0);

        $discount = (float) ($this->diskon_total ?? 0);
        $grandTotal = max(0, ($barangTotal + $jasaTotal) - $discount);

        $this->forceFill([
            'total' => $barangTotal + $jasaTotal,
            'grand_total' => $grandTotal,
        ])->saveQuietly();
    }

    public function recalculatePaymentStatus(): void
    {
        $totalPaid = (float) ($this->pembayaran()->sum('jumlah') ?? 0);

        if ($totalPaid <= 0) {
            return;
        }

        $grandTotal = (float) ($this->grand_total ?? 0);
        $status = $grandTotal > 0 && $totalPaid >= $grandTotal ? 'lunas' : 'belum_lunas';

        $this->forceFill([
            'status_pembayaran' => $status,
        ])->saveQuietly();

        // Clear cache saat status berubah
        $this->clearCalculationCache();
    }

    /**
     * Calculate grand total with caching
     */
    public function calculateGrandTotalCached(): float
    {
        return CacheHelper::calculation(
            CacheHelper::TAG_PENJUALAN,
            $this->id_penjualan,
            function () {
                $barangTotal = $this->items->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga_jual ?? 0));
                $jasaTotal = $this->jasaItems->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga ?? 0));
                $discount = (float) ($this->diskon_total ?? 0);

                return max(0, ($barangTotal + $jasaTotal) - $discount);
            }
        );
    }

    /**
     * Calculate total paid with caching
     */
    public function calculateTotalPaidCached(): float
    {
        return CacheHelper::calculation(
            CacheHelper::TAG_PENJUALAN.':paid',
            $this->id_penjualan,
            function () {
                return (float) $this->pembayaran->sum('jumlah');
            }
        );
    }

    /**
     * Clear calculation cache for this record
     */
    public function clearCalculationCache(): void
    {
        CacheHelper::flush([CacheHelper::TAG_PENJUALAN]);
    }

    protected static function booted(): void
    {
        static::deleting(function (Penjualan $penjualan): void {
            $penjualan->clearCalculationCache();

            // Allow deletion if triggered by TukarTambah cascade
            if (! self::$allowTukarTambahDeletion) {
                // Check if this penjualan belongs to a Tukar Tambah
                if ($penjualan->sumber_transaksi === 'tukar_tambah' || $penjualan->tukarTambah()->exists()) {
                    $ttKode = $penjualan->tukarTambah?->kode ?? 'TT-XXXXX';
                    $errorMessage = "Tidak bisa hapus: Penjualan ini bagian dari Tukar Tambah ({$ttKode}). Hapus dari Tukar Tambah.";

                    // Log ke validation_logs
                    \App\Models\ValidationLog::log([
                        'source_type' => 'Penjualan',
                        'source_action' => 'delete',
                        'validation_type' => 'business_rule',
                        'field_name' => 'id_penjualan',
                        'error_message' => $errorMessage,
                        'error_code' => 'BUSINESS_RULE_TUKAR_TAMBAH_DELETE_BLOCKED',
                        'input_data' => [
                            'penjualan_id' => $penjualan->getKey(),
                            'no_nota' => $penjualan->no_nota,
                            'sumber_transaksi' => $penjualan->sumber_transaksi,
                            'tukar_tambah_kode' => $ttKode,
                            'tukar_tambah_id' => $penjualan->tukarTambah?->getKey(),
                        ],
                        'severity' => 'warning',
                    ]);

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'id_penjualan' => $errorMessage,
                    ]);
                }
            }

            // Delete related items
            $penjualan->items()->get()->each->delete();
            $penjualan->jasaItems()->get()->each->delete();
        });

        static::creating(function ($model) {
            $model->sumber_transaksi = $model->sumber_transaksi ?? 'manual';
            $model->status_dokumen = $model->status_dokumen ?? 'final';

            if (empty($model->no_nota)) {
                if ($model->status_dokumen === 'draft') {
                    $model->no_nota = static::generateDraftNoNota();
                } else {
                    $prefix = $model->sumber_transaksi === 'pos' ? 'POS' : 'PJ';
                    $model->no_nota = static::generateNoNota($prefix);
                }
            }
        });

        static::updated(function (Penjualan $penjualan) {
            $penjualan->clearCalculationCache();
        });
    }

    public function jasaItems()
    {
        return $this->hasMany(PenjualanJasa::class, 'id_penjualan', 'id_penjualan');
    }

    public function tukarTambah()
    {
        return $this->hasOne(TukarTambah::class, 'penjualan_id', 'id_penjualan');
    }

    public function scopePosOnly(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('sumber_transaksi', 'pos')
                ->orWhereNull('sumber_transaksi');
        });
    }

    /**
     * Scope for draft documents
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status_dokumen', 'draft');
    }

    /**
     * Scope for final documents
     */
    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('status_dokumen', 'final');
    }

    /**
     * Check if document is draft
     */
    public function isDraft(): bool
    {
        return $this->status_dokumen === 'draft';
    }

    /**
     * Check if document is final
     */
    public function isFinal(): bool
    {
        return $this->status_dokumen === 'final';
    }

    /**
     * Generate draft number
     */
    public static function generateDraftNoNota(): string
    {
        return DB::transaction(function () {
            $date = now()->format('Ymd');
            $prefix = 'DRAFT-'.$date.'-';

            $latest = static::where('no_nota', 'like', $prefix.'%')
                ->orderBy('no_nota', 'desc')
                ->lockForUpdate()
                ->first();

            $next = 1;
            if ($latest && preg_match('/'.preg_quote($prefix).'(\d+)$/', $latest->no_nota, $m)) {
                $next = (int) $m[1] + 1;
            }

            return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Revert final document to draft
     * Items tetap ada, stok dikembalikan via update status
     */
    public function revertToDraft(): bool
    {
        return DB::transaction(function () {
            // Reload untuk memastikan data terbaru
            $this->refresh();

            \Illuminate\Support\Facades\Log::info('revertToDraft started', [
                'id' => $this->id_penjualan,
                'status_dokumen' => $this->status_dokumen,
                'items_count' => $this->items()->count(),
            ]);

            // Validasi: hanya final yang bisa direvert
            if ($this->isDraft()) {
                throw new \Exception('Transaksi sudah dalam status draft (status: '.$this->status_dokumen.')');
            }

            // Validasi: tidak bisa revert jika sudah ada pembayaran real (jumlah > 0)
            if ($this->pembayaran()->where('jumlah', '>', 0)->exists()) {
                throw new \Exception('Tidak bisa revert: Sudah ada pembayaran. Gunakan retur.');
            }

            $itemsCount = $this->items()->count();

            // Kembalikan stok untuk setiap item (manual, karena hooks tidak jalan untuk final)
            foreach ($this->items as $item) {
                $batchId = (int) $item->id_pembelian_item;
                $qty = (int) $item->qty;

                if ($batchId && $qty > 0) {
                    $batch = PembelianItem::query()->find($batchId);
                    if ($batch) {
                        $qtyColumn = PembelianItem::qtySisaColumn();
                        $batch->{$qtyColumn} = (int) ($batch->{$qtyColumn} ?? 0) + $qty;
                        $batch->save();

                        \Illuminate\Support\Facades\Log::info('Stock restored', [
                            'batch_id' => $batchId,
                            'qty' => $qty,
                        ]);
                    }
                }
            }

            // Update status ke draft
            $this->forceFill([
                'status_dokumen' => 'draft',
            ])->saveQuietly();

            // Refresh setelah update
            $this->refresh();

            $this->clearCalculationCache();

            \Illuminate\Support\Facades\Log::info('revertToDraft completed', [
                'new_status' => $this->status_dokumen,
                'items_preserved' => $itemsCount,
            ]);

            return true;
        });
    }

    /**
     * Finalize draft document
     */
    public function finalize(): bool
    {
        return DB::transaction(function () {
            // Validasi: hanya draft yang bisa difinalisasi
            if ($this->isFinal()) {
                throw new \Exception('Transaksi sudah final');
            }

            // Generate nomor nota final
            $prefix = $this->sumber_transaksi === 'pos' ? 'POS' : 'PJ';
            $finalNoNota = static::generateNoNota($prefix);

            // Update status dan nomor nota
            $this->forceFill([
                'status_dokumen' => 'final',
                'no_nota' => $finalNoNota,
            ])->saveQuietly();

            $this->clearCalculationCache();

            return true;
        });
    }
}
