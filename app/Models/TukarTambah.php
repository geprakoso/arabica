<?php

namespace App\Models;

use App\Support\CacheHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TukarTambah extends Model
{
    use HasFactory;

    protected $table = 'tb_tukar_tambah';

    protected $primaryKey = 'id_tukar_tambah';

    protected $fillable = [
        'no_nota',
        'tanggal',
        'catatan',
        'id_karyawan',
        'id_member',
        'penjualan_id',
        'pembelian_id',
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
        'tanggal' => 'date',
        'foto_dokumen' => 'array',
        'is_locked' => 'boolean',
        'void_used' => 'boolean',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TukarTambah $tukarTambah): void {
            if (blank($tukarTambah->no_nota)) {
                $tukarTambah->no_nota = self::generateNoNota();
            }
        });

        static::deleting(function (TukarTambah $tukarTambah): void {
            // Clear cache saat dihapus
            $tukarTambah->clearCalculationCache();

            DB::transaction(function () use ($tukarTambah): void {
                $penjualanId = $tukarTambah->penjualan_id;
                $pembelian = $tukarTambah->pembelian;

                if ($pembelian) {
                    $externalReferences = $tukarTambah->getExternalPenjualanReferences();
                    $externalPenjualanNotas = $externalReferences
                        ->pluck('nota')
                        ->filter()
                        ->values();

                    if ($externalPenjualanNotas->isNotEmpty()) {
                        $notaList = $externalPenjualanNotas->implode(', ');

                        // Log ke validation_logs
                        \App\Models\ValidationLog::log([
                            'source_type' => 'TukarTambah',
                            'source_action' => 'delete',
                            'validation_type' => 'business_rule',
                            'field_name' => 'pembelian_id',
                            'error_message' => "Tidak bisa hapus: item pembelian dipakai transaksi lain. Nota: {$notaList}.",
                            'error_code' => 'BUSINESS_RULE_DELETE_BLOCKED',
                            'input_data' => [
                                'tukar_tambah_id' => $tukarTambah->getKey(),
                                'no_nota' => $tukarTambah->no_nota,
                                'external_notas' => $externalPenjualanNotas->toArray(),
                                'external_references' => $externalReferences->toArray(),
                            ],
                            'severity' => 'warning',
                        ]);

                        throw ValidationException::withMessages([
                            'pembelian_id' => 'Tidak bisa hapus: item pembelian dipakai transaksi lain. Nota: '.$notaList.'.',
                        ]);
                    }
                }

                // Set flags to allow cascade deletion
                \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                \App\Models\Pembelian::$allowTukarTambahDeletion = true;

                try {
                    $tukarTambah->penjualan?->delete();
                    $tukarTambah->pembelian?->delete();
                } finally {
                    // Reset flags
                    \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                    \App\Models\Pembelian::$allowTukarTambahDeletion = false;
                }
            });
        });

        static::updated(function (TukarTambah $tukarTambah) {
            $tukarTambah->clearCalculationCache();
        });
    }

    /**
     * Clear calculation cache for this record
     */
    public function clearCalculationCache(): void
    {
        CacheHelper::flush([CacheHelper::TAG_TUKAR_TAMBAH]);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id', 'id_penjualan');
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'id_pembelian');
    }

    public function member()
    {
        return $this->belongsTo(Member::class, 'id_member');
    }

    /**
     * Get the columns that should be searched.
     */
    public function getSearchableColumns(): array
    {
        return [
            'no_nota',
            'member.nama_member',
            'member.no_hp',
        ];
    }

    public function isEditLocked(): bool
    {
        return $this->getExternalPenjualanReferences()->isNotEmpty();
    }

    public function getEditBlockedMessage(): string
    {
        $notaList = $this->getExternalPenjualanReferences()
            ->pluck('nota')
            ->filter()
            ->values();
        $suffix = $notaList->isNotEmpty()
            ? ' Nota: '.$notaList->implode(', ').'.'
            : '';

        return 'Tukar tambah tidak bisa diedit karena item pembelian dipakai transaksi lain.'.$suffix;
    }

    // ============================================================
    // STATE MACHINE (mirroring Penjualan)
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

    public function canPost(): bool
    {
        return $this->isDraft() && ! $this->is_locked;
    }

    public function canVoid(): bool
    {
        return $this->isFinal()
            && ! $this->is_locked
            && ! $this->void_used;
    }

    public function canLock(): bool
    {
        return $this->isFinal() && ! $this->is_locked;
    }

    public function canDelete(): bool
    {
        return $this->isDraft()
            && ! $this->is_locked;
    }

    public function post(): void
    {
        if (! $this->canPost()) {
            throw new \RuntimeException('Tukar Tambah tidak bisa di-post.');
        }

        DB::transaction(function (): void {
            $this->update([
                'status_dokumen' => 'final',
                'posted_at' => now(),
                'posted_by_id' => auth()->id(),
            ]);

            // Sync Penjualan & Pembelian
            $this->penjualan?->update(['status_dokumen' => 'final']);
            $this->pembelian?->update(['is_locked' => true]);
        });
    }

    public function voidToDraft(): void
    {
        if (! $this->canVoid()) {
            throw new \RuntimeException('Tukar Tambah tidak bisa di-void.');
        }

        $this->update([
            'status_dokumen' => 'draft',
            'void_used' => true,
            'voided_at' => now(),
            'voided_by_id' => auth()->id(),
        ]);

        // Penjualan: kembali ke draft (sama seperti Penjualan standar)
        $this->penjualan?->update([
            'status_dokumen' => 'draft',
            'void_used' => true,
            'voided_at' => now(),
            'voided_by_id' => auth()->id(),
        ]);
        // Pembelian tetap locked
    }

    public function lockFinal(): void
    {
        if (! $this->canLock()) {
            throw new \RuntimeException('Tukar Tambah tidak bisa di-lock.');
        }

        DB::transaction(function (): void {
            $this->update(['is_locked' => true]);
            $this->penjualan?->update(['is_locked' => true]);
            $this->pembelian?->update(['is_locked' => true]);
        });
    }

    public function getKodeAttribute(): string
    {
        return 'TT-'.str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT);
    }

    public static function generateNoNota(): string
    {
        $date = now()->format('Ym');
        $prefix = 'TT-'.$date.'-';

        $latest = static::where('no_nota', 'like', $prefix.'%')
            ->orderBy('no_nota', 'desc')
            ->first();

        $next = 1;
        if ($latest && preg_match('/'.preg_quote($prefix, '/').'(\d+)$/', $latest->no_nota, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    public function getExternalPenjualanReferences(): Collection
    {
        $pembelian = $this->pembelian;
        if (! $pembelian) {
            return collect();
        }

        $penjualanId = $this->penjualan_id;

        return $pembelian->items()
            ->whereHas('penjualanItems', function ($query) use ($penjualanId): void {
                if ($penjualanId) {
                    $query->where('id_penjualan', '!=', $penjualanId);
                }
            })
            ->with(['penjualanItems.penjualan'])
            ->get()
            ->flatMap(fn ($item) => $item->penjualanItems)
            ->filter(fn ($item) => ! $penjualanId || (int) $item->id_penjualan !== $penjualanId)
            ->map(function ($item) {
                if (! $item->penjualan) {
                    return null;
                }

                return [
                    'id' => (int) $item->penjualan->getKey(),
                    'nota' => $item->penjualan->no_nota,
                ];
            })
            ->filter()
            ->unique('id')
            ->values();
    }
}
