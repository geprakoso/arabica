<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'tanggal',
        'status',
        'gudang_id',
        'user_id',
        'posted_by_id',
        'posted_at',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockOpname $opname): void {
            $opname->kode ??= self::generateKode();
            $opname->status ??= 'draft';
        });
    }

    public static function generateKode(): string
    {
        $prefix = 'SO-' . now()->format('Ymd') . '-';

        $number = (int) self::where('kode', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(kode, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        // Increment until we find an unused code to avoid duplicate key on race or stale form.
        do {
            $number++;
            $kode = $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        } while (self::where('kode', $kode)->exists());

        return $kode;
    }

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Posting stock opname dengan atomic transaction
     * Semua item diproses dalam satu transaction, gagal semua atau sukses semua
     *
     * @param User|null $user User yang melakukan posting
     * @return bool True jika berhasil
     * @throws ValidationException Jika validasi gagal
     * @throws \Exception Jika terjadi error database
     */
    public function post(User $user = null): bool
    {
        if ($this->isPosted()) {
            throw ValidationException::withMessages([
                'status' => 'Stock opname sudah diposting sebelumnya.',
            ]);
        }

        // Validasi: harus punya items
        if ($this->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Stock opname harus memiliki minimal 1 item.',
            ]);
        }

        return DB::transaction(function () use ($user) {
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($this->items as $item) {
                // Skip jika tidak ada selisih
                if ($item->selisih === 0) {
                    $skippedCount++;
                    continue;
                }

                // Validasi: batch harus ada
                if (! $item->pembelianItem) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}" => "Item #{$item->id}: Batch pembelian tidak ditemukan.",
                    ]);
                }

                // Validasi: batch tidak sedang dalam RMA aktif
                $hasActiveRma = $item->pembelianItem
                    ->rmas()
                    ->whereIn('status_garansi', Rma::activeStatuses())
                    ->exists();

                if ($hasActiveRma) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}" => "Item #{$item->id}: Batch sedang dalam proses RMA aktif, tidak dapat diopname.",
                    ]);
                }

                // Validasi: stok tidak boleh negatif setelah adjustment
                $currentStock = $item->stok_sistem;
                $newStock = $currentStock + $item->selisih;

                if ($newStock < 0) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}" => "Item #{$item->id}: Selisih mengakibatkan stok negatif ({$currentStock} + {$item->selisih} = {$newStock}).",
                    ]);
                }

                // Apply ke batch menggunakan StockBatch
                $item->applyToBatch();
                $processedCount++;
            }

            // Update status opname
            $this->forceFill([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by_id' => $user?->getKey(),
            ])->save();

            // Log activity
            \Log::info('Stock opname posted', [
                'opname_id' => $this->id,
                'kode' => $this->kode,
                'processed_items' => $processedCount,
                'skipped_items' => $skippedCount,
                'posted_by' => $user?->id,
            ]);

            return true;
        });
    }

    /**
     * Batal posting (unpost) - untuk keperluan tertentu
     * ⚠️ Hati-hati menggunakan ini karena bisa mengacaukan stok
     */
    public function unpost(): bool
    {
        if (! $this->isPosted()) {
            throw ValidationException::withMessages([
                'status' => 'Stock opname belum diposting.',
            ]);
        }

        // TODO: Implement reverse logic jika diperlukan
        // Ini kompleks karena perlu mengurangi/menambah kembali stok
        // sehingga kembali ke kondisi sebelum posting

        throw new \Exception('Unpost belum diimplementasikan.');
    }

    /**
     * Get summary untuk ditampilkan sebelum posting
     */
    public function getSummary(): array
    {
        $totalItems = $this->items()->count();
        $totalSelisihPositif = $this->items()->where('selisih', '>', 0)->sum('selisih');
        $totalSelisihNegatif = abs($this->items()->where('selisih', '<', 0)->sum('selisih'));
        $totalTanpaSelisih = $this->items()->where('selisih', 0)->count();

        return [
            'total_items' => $totalItems,
            'total_selisih_positif' => $totalSelisihPositif,
            'total_selisih_negatif' => $totalSelisihNegatif,
            'total_tanpa_selisih' => $totalTanpaSelisih,
        ];
    }
}
