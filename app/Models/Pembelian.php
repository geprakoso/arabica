<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'tb_pembelian';
    protected $primaryKey = 'id_pembelian';

    protected $fillable = [
        'no_po',
        'nota_supplier',
        'tanggal',
        'harga_jual',
        'catatan',
        'tipe_pembelian',
        'jenis_pembayaran',
        'tgl_tempo',
        'id_karyawan',
        'id_supplier',
    ];

    protected static function booted(): void
    {
        static::creating(function (Pembelian $pembelian): void {
            if (blank($pembelian->no_po)) {
                $pembelian->no_po = self::generatePO();
            }
        });

        static::deleting(function (Pembelian $pembelian): void {
            // Check if this pembelian belongs to a Tukar Tambah
            if ($pembelian->tukarTambah()->exists()) {
                $ttKode = $pembelian->tukarTambah?->kode ?? 'TT-XXXXX';
                
                throw ValidationException::withMessages([
                    'id_pembelian' => "Tidak bisa hapus: Pembelian ini bagian dari Tukar Tambah ({$ttKode}). Hapus dari Tukar Tambah.",
                ]);
            }
            
            $externalPenjualanNotas = $pembelian->items()
                ->whereHas('penjualanItems')
                ->with(['penjualanItems.penjualan'])
                ->get()
                ->flatMap(fn($item) => $item->penjualanItems)
                ->map(fn($item) => $item->penjualan?->no_nota)
                ->filter()
                ->unique()
                ->values();

            if ($externalPenjualanNotas->isNotEmpty()) {
                $notaList = $externalPenjualanNotas->implode(', ');

                throw ValidationException::withMessages([
                    'id_pembelian' => 'Tidak bisa hapus: item pembelian dipakai transaksi lain. Nota: ' . $notaList . '.',
                ]);
            }
        });
    }

    public static function generatePO(): string
    {
        $date = now()->format('Ym');
        $prefix = 'PO-' . $date . '-';

        $latest = self::where('no_po', 'like', $prefix . '%')
            ->orderBy('no_po', 'desc')
            ->first();

        $next = 1;
        if ($latest && preg_match('/' . preg_quote($prefix, '/') . '(\d+)$/', $latest->no_po, $m)) {
            $next = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    protected $casts = [
        'tanggal' => 'date',
        'tgl_tempo' => 'date',
        'harga_jual' => 'decimal:2',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function requestOrders()
    {
        return $this->belongsToMany(RequestOrder::class, 'pembelian_request_order', 'pembelian_id', 'request_order_id')
            ->withTimestamps();
    }

    public function items()
    {
        return $this->hasMany(PembelianItem::class, 'id_pembelian', 'id_pembelian');
    }

    public function pembayaran()
    {
        return $this->hasMany(PembelianPembayaran::class, 'id_pembelian', 'id_pembelian');
    }

    public function jasaItems()
    {
        return $this->hasMany(PembelianJasa::class, 'id_pembelian', 'id_pembelian');
    }

    public function isEditLocked(): bool
    {
        $itemTable = (new PembelianItem())->getTable();
        $qtyMasukColumn = PembelianItem::qtyMasukColumn();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        return $this->items()
            ->where(function ($query) use ($itemTable, $qtyMasukColumn, $qtySisaColumn) {
                $query->whereColumn($itemTable . '.' . $qtySisaColumn, '<', $itemTable . '.' . $qtyMasukColumn)
                    ->orWhereHas('penjualanItems');
            })
            ->exists();
    }

    public function getEditBlockedMessage(): string
    {
        $notaList = $this->getBlockedPenjualanReferences()
            ->pluck('nota')
            ->filter()
            ->values();

        $suffix = $notaList->isNotEmpty()
            ? ' Nota: ' . $notaList->implode(', ') . '.'
            : '';

        return 'Pembelian tidak bisa diedit karena item sudah dipakai transaksi lain.' . $suffix;
    }

    public function getBlockedPenjualanReferences(): Collection
    {
        return $this->items()
            ->whereHas('penjualanItems')
            ->with(['penjualanItems.penjualan:id_penjualan,no_nota'])
            ->get()
            ->flatMap(fn($item) => $item->penjualanItems)
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

    public function tukarTambah()
    {
        return $this->hasOne(TukarTambah::class, 'pembelian_id', 'id_pembelian');
    }

    protected ?float $cachedTotalPembelian = null;

    public function calculateTotalPembelian(): float
    {
        // Return cached value if available
        if ($this->cachedTotalPembelian !== null) {
            return $this->cachedTotalPembelian;
        }

        // Use loaded relations if available (avoids N+1)
        if ($this->relationLoaded('items') && $this->relationLoaded('jasaItems')) {
            $itemsTotal = (float) $this->items->sum(fn($item) => ($item->qty ?? 0) * ($item->hpp ?? 0));
            $jasaTotal = (float) $this->jasaItems->sum(fn($item) => ($item->qty ?? 0) * ($item->harga ?? 0));
        } else {
            // Fallback to database queries
            $itemsTotal = (float) ($this->items()
                ->selectRaw('COALESCE(SUM(qty * hpp), 0) as total')
                ->value('total') ?? 0);
            $jasaTotal = (float) ($this->jasaItems()
                ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
                ->value('total') ?? 0);
        }

        return $this->cachedTotalPembelian = $itemsTotal + $jasaTotal;
    }

    public function recalculatePaymentStatus(): void
    {
        $total = $this->calculateTotalPembelian();
        $totalPaid = (float) ($this->pembayaran()->sum('jumlah') ?? 0);
        $status = $total <= 0 || $totalPaid >= $total ? 'lunas' : 'tempo';

        if ($this->jenis_pembayaran === $status) {
            return;
        }

        $this->forceFill([
            'jenis_pembayaran' => $status,
        ])->saveQuietly();
    }
}
