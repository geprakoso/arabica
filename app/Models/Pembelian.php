<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'tb_pembelian';
    protected $primaryKey = 'id_pembelian';

    protected $fillable = [
        'no_po',
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
        $prefix = 'PO-';

        $lastNumber = self::where('no_po', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(no_po, 4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
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

    public function tukarTambah()
    {
        return $this->hasOne(TukarTambah::class, 'pembelian_id', 'id_pembelian');
    }

    public function calculateTotalPembelian(): float
    {
        $itemsTotal = (float) ($this->items()
            ->selectRaw('COALESCE(SUM(qty * hpp), 0) as total')
            ->value('total') ?? 0);
        $jasaTotal = (float) ($this->jasaItems()
            ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
            ->value('total') ?? 0);

        return $itemsTotal + $jasaTotal;
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
