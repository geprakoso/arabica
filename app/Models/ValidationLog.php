<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ValidationLog extends Model
{
    protected $table = 'validation_logs';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'source_type',
        'source_action',
        'user_id',
        'user_name',
        'validation_type',
        'field_name',
        'error_message',
        'error_code',
        'input_data',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'severity',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'input_data' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Create a new validation log entry
     */
    public static function log(array $data): self
    {
        $user = auth()->user();

        return self::create([
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),
            'source_type' => $data['source_type'],
            'source_action' => $data['source_action'],
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'Guest',
            'validation_type' => $data['validation_type'],
            'field_name' => $data['field_name'] ?? null,
            'error_message' => $data['error_message'],
            'error_code' => $data['error_code'] ?? null,
            'input_data' => $data['input_data'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->url(),
            'method' => request()->method(),
            'severity' => $data['severity'] ?? 'warning',
            'created_at' => now(),
        ]);
    }

    /**
     * Scope: Unresolved logs
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope: By source type
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source_type', $source);
    }

    /**
     * Scope: By validation type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('validation_type', $type);
    }

    /**
     * Scope: Recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: By severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Mark log as resolved
     */
    public function markAsResolved(?string $notes = null, ?int $resolvedBy = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Get severity color for Filament
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'danger',
            'critical' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get validation type label
     */
    public function getValidationTypeLabel(): string
    {
        return match ($this->validation_type) {
            'duplicate' => 'Duplikat',
            'stock' => 'Stok',
            'required' => 'Wajib Diisi',
            'format' => 'Format',
            'business_rule' => 'Aturan Bisnis',
            default => $this->validation_type,
        };
    }
}
