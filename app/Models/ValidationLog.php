<?php

namespace App\Models;

use App\Enums\Severity;
use App\Enums\SourceType;
use App\Enums\ValidationType;
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
        'created_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (ValidationLog $log) {
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    protected $casts = [
        'input_data' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'severity' => Severity::class,
        'validation_type' => ValidationType::class,
        'source_type' => SourceType::class,
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

    public function markAsResolved(?string $notes = null, ?int $resolvedBy = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id(),
            'resolution_notes' => $notes,
        ]);
    }
}
