<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Rma extends Model
{
    use HasFactory;

    protected $table = 'tb_rma';

    protected $primaryKey = 'id_rma';

    protected $fillable = [
        'tanggal',
        'id_pembelian_item',
        'status_garansi',
        'rma_di_mana',
        'foto_dokumen',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'foto_dokumen' => 'array',
    ];

    public const STATUS_DI_PACKING = 'di_packing';
    public const STATUS_PROSES_KLAIM = 'proses_klaim';
    public const STATUS_SELESAI = 'selesai';

    protected static function booted(): void
    {
        static::creating(function (Rma $rma): void {
            $rma->status_garansi ??= self::STATUS_DI_PACKING;

            if (! $rma->id_pembelian_item) {
                return;
            }

            if (self::hasActiveRmaForBatch((int) $rma->id_pembelian_item)) {
                throw ValidationException::withMessages([
                    'id_pembelian_item' => 'Batch ini masih dalam proses RMA aktif.',
                ]);
            }
        });

        static::updating(function (Rma $rma): void {
            $batchId = (int) ($rma->id_pembelian_item ?? 0);
            if ($batchId < 1) {
                return;
            }

            $status = $rma->status_garansi;
            $isActive = in_array($status, self::activeStatuses(), true);

            if ($isActive && self::hasActiveRmaForBatch($batchId, (int) $rma->getKey())) {
                throw ValidationException::withMessages([
                    'id_pembelian_item' => 'Batch ini masih dalam proses RMA aktif.',
                ]);
            }
        });
    }

    public static function activeStatuses(): array
    {
        return [self::STATUS_DI_PACKING, self::STATUS_PROSES_KLAIM];
    }

    public static function hasActiveRmaForBatch(int $batchId, ?int $exceptId = null): bool
    {
        $query = self::query()
            ->where('id_pembelian_item', $batchId)
            ->whereIn('status_garansi', self::activeStatuses());

        if ($exceptId) {
            $query->where($query->getModel()->getKeyName(), '!=', $exceptId);
        }

        return $query->exists();
    }

    public function pembelianItem()
    {
        return $this->belongsTo(PembelianItem::class, 'id_pembelian_item', 'id_pembelian_item');
    }
}
