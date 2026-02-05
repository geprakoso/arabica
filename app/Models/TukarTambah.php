<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use Illuminate\Database\Eloquent\SoftDeletes;

class TukarTambah extends Model
{
    use HasFactory, SoftDeletes;

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
    ];

    protected $casts = [
        'tanggal' => 'date',
        'foto_dokumen' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (TukarTambah $tukarTambah): void {
            if (blank($tukarTambah->no_nota)) {
                $tukarTambah->no_nota = self::generateNoNota();
            }
        });

        static::deleting(function (TukarTambah $tukarTambah): void {
            // Only cascade delete relations if this is a FORCE delete
            if ($tukarTambah->isForceDeleting()) {
                DB::transaction(function () use ($tukarTambah): void {
                    $penjualanId = $tukarTambah->penjualan_id;
                    $pembelian = $tukarTambah->pembelian;

                    if ($pembelian) {
                        $externalPenjualanNotas = $tukarTambah->getExternalPenjualanReferences()
                            ->pluck('nota')
                            ->filter()
                            ->values();

                        if ($externalPenjualanNotas->isNotEmpty()) {
                            $notaList = $externalPenjualanNotas->implode(', ');

                            throw ValidationException::withMessages([
                                'pembelian_id' => 'Tidak bisa hapus: item pembelian dipakai transaksi lain. Nota: ' . $notaList . '.',
                            ]);
                        }
                    }

                    // Set flags to allow cascade deletion
                    \App\Models\Penjualan::$allowTukarTambahDeletion = true;
                    \App\Models\Pembelian::$allowTukarTambahDeletion = true;
                    
                    try {
                        $tukarTambah->penjualan?->delete(); // This will soft delete if Penjualan uses SoftDeletes
                        $tukarTambah->pembelian?->delete(); // This will soft delete if Pembelian uses SoftDeletes
                    } finally {
                        // Reset flags
                        \App\Models\Penjualan::$allowTukarTambahDeletion = false;
                        \App\Models\Pembelian::$allowTukarTambahDeletion = false;
                    }
                });
            }
        });
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
            ? ' Nota: ' . $notaList->implode(', ') . '.'
            : '';

        return 'Tukar tambah tidak bisa diedit karena item pembelian dipakai transaksi lain.' . $suffix;
    }

    public function getKodeAttribute(): string
    {
        return 'TT-' . str_pad((string) $this->getKey(), 5, '0', STR_PAD_LEFT);
    }

    public static function generateNoNota(): string
    {
        $date = now()->format('Ym');
        $prefix = 'TT-' . $date . '-';

        $latest = static::where('no_nota', 'like', $prefix . '%')
            ->orderBy('no_nota', 'desc')
            ->first();

        $next = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest->no_nota, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
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
            ->flatMap(fn($item) => $item->penjualanItems)
            ->filter(fn($item) => ! $penjualanId || (int) $item->id_penjualan !== $penjualanId)
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
