<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail untuk semua perubahan stok
 * Setiap perubahan qty di StockBatch tercatat di sini
 */
class StockMutation extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_batch_id',
        'type',
        'qty_change',
        'qty_before',
        'qty_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'qty_change' => 'integer',
        'qty_before' => 'integer',
        'qty_after' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke StockBatch
     */
    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    /**
     * Relasi ke user yang melakukan perubahan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation ke reference (Pembelian, Penjualan, StockOpname, dll)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    /**
     * Scope untuk filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope untuk mutasi masuk (positif)
     */
    public function scopeIncoming($query)
    {
        return $query->where('qty_change', '>', 0);
    }

    /**
     * Scope untuk mutasi keluar (negatif)
     */
    public function scopeOutgoing($query)
    {
        return $query->where('qty_change', '<', 0);
    }

    /**
     * Accessor untuk format qty_change dengan tanda +/-
     */
    public function getFormattedQtyChangeAttribute(): string
    {
        $prefix = $this->qty_change > 0 ? '+' : '';
        return "{$prefix}{$this->qty_change}";
    }

    /**
     * Boot model
     */
    protected static function booted(): void
    {
        static::creating(function (StockMutation $mutation) {
            // Auto-set user_id jika belum diisi
            if (empty($mutation->user_id) && auth()->check()) {
                $mutation->user_id = auth()->id();
            }
        });
    }
}
