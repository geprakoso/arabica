<?php

namespace App\Models;

use App\Enums\MetodeBayar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Models\AkunTransaksi;
use App\Models\PenjualanPembayaran;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = 'tb_penjualan';

    protected $primaryKey = 'id_penjualan';

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
    ];

    protected $casts = [
        'tanggal_penjualan' => 'date',
        'metode_bayar' => MetodeBayar::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Penjualan $penjualan): void {
            // Check if this penjualan belongs to a Tukar Tambah
            if ($penjualan->sumber_transaksi === 'tukar_tambah' || $penjualan->tukarTambah()->exists()) {
                $ttKode = $penjualan->tukarTambah?->kode ?? 'TT-XXXXX';
                
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'id_penjualan' => "Tidak bisa hapus: Penjualan ini bagian dari Tukar Tambah ({$ttKode}). Hapus dari Tukar Tambah.",
                ]);
            }
            
            $penjualan->items()->get()->each->delete();
            $penjualan->jasaItems()->get()->each->delete();
        });

        static::creating(function ($model) {
            $model->sumber_transaksi = $model->sumber_transaksi ?? 'manual';

            if (empty($model->no_nota)) {
                $prefix = $model->sumber_transaksi === 'pos' ? 'POS' : 'PJ';
                $model->no_nota = static::generateNoNota($prefix);
            }
        });
    }

    public static function generateNoNota(string $prefixCode = 'PJ'): string
    {
        return DB::transaction(function () use ($prefixCode) {
            $date = now()->format('Ym');
            $prefix = $prefixCode . '-' . $date . '-';

            $latest = static::where('no_nota', 'like', $prefix . '%')
                ->orderBy('no_nota', 'desc')
                ->lockForUpdate()
                ->first();

            $next = 1;
            if ($latest && preg_match('/' . preg_quote($prefix) . '(\d+)$/', $latest->no_nota, $m)) {
                $next = (int) $m[1] + 1;
            }

            return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
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
        $total = (float) ($this->items()
            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
            ->value('total') ?? 0);

        $discount = (float) ($this->diskon_total ?? 0);
        $grandTotal = max(0, $total - $discount);

        $this->forceFill([
            'total' => $total,
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
