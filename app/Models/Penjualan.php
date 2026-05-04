<?php

namespace App\Models;

use App\Enums\MetodeBayar;
use App\Support\CacheHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        'is_nerfed',
        'foto_dokumen',
        'status_dokumen',
        'is_locked',
        'void_used',
        'posted_at',
        'posted_by_id',
        'voided_at',
        'voided_by_id',
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
        'metode_bayar' => MetodeBayar::class,
        'is_nerfed' => 'boolean',
        'foto_dokumen' => 'array',
        'is_locked' => 'boolean',
        'void_used' => 'boolean',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    // ============================================================
    // STATE MACHINE
    // ============================================================

    public function isDraft(): bool
    {
        return $this->status_dokumen === 'draft';
    }

    public function isFinal(): bool
    {
        return $this->status_dokumen === 'final';
    }

    public function canEditItems(): bool
    {
        return $this->isDraft()
            && ! $this->is_locked;
    }

    public function canEditJasa(): bool
    {
        return $this->canEditItems();
    }

    public function canEditPayment(): bool
    {
        return $this->isDraft() && ! $this->is_locked;
    }

    public function canVoid(): bool
    {
        return $this->isFinal()
            && ! $this->is_locked
            && ! $this->void_used;
    }

    public function canPost(): bool
    {
        return $this->isDraft() && ! $this->is_locked;
    }

    public function canLock(): bool
    {
        return $this->isFinal() && ! $this->is_locked;
    }

    public function post(): void
    {
        if (! $this->canPost()) {
            throw new \RuntimeException('Penjualan tidak bisa di-post.');
        }

        DB::transaction(function () {
            $this->update([
                'status_dokumen' => 'final',
                'posted_at' => now(),
                'posted_by_id' => auth()->id(),
            ]);
        });
    }

    public function voidToDraft(): void
    {
        if (! $this->canVoid()) {
            throw new \RuntimeException('Penjualan tidak bisa di-void.');
        }

        $this->update([
            'status_dokumen' => 'draft',
            'void_used' => true,
            'voided_at' => now(),
            'voided_by_id' => auth()->id(),
        ]);
        // Stok TIDAK dikembalikan!
        // Item & Jasa tetap locked!
    }

    public function lockFinal(): void
    {
        if (! $this->canLock()) {
            throw new \RuntimeException('Penjualan tidak bisa di-lock.');
        }

        $this->update(['is_locked' => true]);
    }

    public function canDelete(): bool
    {
        if (filled($this->no_tt) || $this->tukarTambah()->exists() || $this->sumber_transaksi === 'tukar_tambah') {
            return false;
        }
        return true;
    }

    public function delete(): ?bool
    {
        if (! $this->canDelete()) {
            throw ValidationException::withMessages([
                'delete' => 'Penjualan tidak bisa dihapus karena terkait Tukar Tambah.'
            ]);
        }

        return DB::transaction(function () {
            // Items akan dihapus oleh event deleting di booted()
            // Setiap item deletion otomatis mengembalikan stok via PenjualanItem observer
            // Hapus mutations setelah items dihapus
            $itemIds = $this->items->pluck('id_penjualan_item')->toArray();

            $result = parent::delete();

            // Hapus mutations yang tersisa
            StockMutation::where('reference_type', 'Penjualan')
                ->where('reference_id', $this->id_penjualan)
                ->delete();

            StockMutation::where('reference_type', 'PenjualanItem')
                ->whereIn('reference_id', $itemIds)
                ->delete();

            return $result;
        });
    }

    // ============================================================
    // STATUS PEMBAYARAN
    // ============================================================

    public function getStatusPembayaranAttribute(): string
    {
        $totalPaid = (float) $this->pembayaran()->sum('jumlah');
        $grandTotal = (float) ($this->grand_total ?? 0);

        return $totalPaid >= $grandTotal ? 'LUNAS' : 'TEMPO';
    }

    // ============================================================
    // EXISTING METHODS
    // ============================================================

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
            $model->status_dokumen = $model->status_dokumen ?? 'draft';

            if (empty($model->no_nota)) {
                $prefix = $model->sumber_transaksi === 'pos' ? 'POS' : 'PJ';
                $model->no_nota = static::generateNoNota($prefix);
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
}
