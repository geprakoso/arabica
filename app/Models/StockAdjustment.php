<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockAdjustment extends Model
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
        'sumber',
        'sumber_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'posted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockAdjustment $adjustment): void {
            $adjustment->kode ??= self::generateKode();
            $adjustment->status ??= 'draft';
        });
    }

    public static function generateKode(): string
    {
        $prefix = 'SA-' . now()->format('Ymd') . '-';

        $latest = self::where('kode', 'like', $prefix . '%')
            ->orderByDesc('kode')
            ->first();

        $number = 1;

        if ($latest && Str::startsWith($latest->kode, $prefix)) {
            $number = ((int) Str::after($latest->kode, $prefix)) + 1;
        }

        return $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(StockAdjustmentItem::class);
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

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    /**
     * Posting stock adjustment dengan atomic transaction
     * Semua item diproses dalam satu transaction
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
                'status' => 'Penyesuaian stok sudah diposting sebelumnya.',
            ]);
        }

        // Validasi: harus punya items
        if ($this->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Penyesuaian stok harus memiliki minimal 1 item.',
            ]);
        }

        return DB::transaction(function () use ($user) {
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($this->items as $item) {
                // Skip jika qty = 0
                if ((int) $item->qty === 0) {
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
                        "items.{$item->id}" => "Item #{$item->id}: Batch sedang dalam proses RMA aktif, tidak dapat di-adjust.",
                    ]);
                }

                // Validasi: stok tidak boleh negatif setelah adjustment
                $currentStock = $item->pembelianItem->qty_sisa ?? 0;
                $newStock = $currentStock + (int) $item->qty;

                if ($newStock < 0) {
                    throw ValidationException::withMessages([
                        "items.{$item->id}" => "Item #{$item->id}: Adjustment mengakibatkan stok negatif ({$currentStock} + {$item->qty} = {$newStock}).",
                    ]);
                }

                // Apply ke batch menggunakan StockBatch
                $item->applyToBatch();
                $processedCount++;
            }

            // Update status adjustment
            $this->forceFill([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by_id' => $user?->getKey(),
            ])->save();

            // Log activity
            \Log::info('Stock adjustment posted', [
                'adjustment_id' => $this->id,
                'kode' => $this->kode,
                'processed_items' => $processedCount,
                'skipped_items' => $skippedCount,
                'posted_by' => $user?->id,
            ]);

            return true;
        });
    }

    /**
     * Get summary untuk ditampilkan sebelum posting
     */
    public function getSummary(): array
    {
        $totalItems = $this->items()->count();
        $totalPenambahan = $this->items()->where('qty', '>', 0)->sum('qty');
        $totalPengurangan = abs($this->items()->where('qty', '<', 0)->sum('qty'));
        $totalTanpaPerubahan = $this->items()->where('qty', 0)->count();

        return [
            'total_items' => $totalItems,
            'total_penambahan' => $totalPenambahan,
            'total_pengurangan' => $totalPengurangan,
            'total_tanpa_perubahan' => $totalTanpaPerubahan,
        ];
    }
}
