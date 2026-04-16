<?php

namespace App\Services;

use App\Models\ValidationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ValidationLogger
{
    private static ?string $currentUuid = null;

    /**
     * Start batch logging untuk satu form submission
     */
    public static function startBatch(): string
    {
        self::$currentUuid = (string) Str::uuid();

        return self::$currentUuid;
    }

    /**
     * Get current batch UUID
     */
    public static function getBatchUuid(): ?string
    {
        return self::$currentUuid;
    }

    /**
     * End batch logging
     */
    public static function endBatch(): void
    {
        self::$currentUuid = null;
    }

    /**
     * Log single validation error
     */
    public static function log(
        string $sourceType,      // 'Penjualan', 'TukarTambah', etc
        string $sourceAction,    // 'create', 'update'
        string $validationType,  // 'duplicate', 'stock', 'required'
        string $errorMessage,
        array $options = []
    ): ValidationLog {
        $log = ValidationLog::log([
            'uuid' => self::$currentUuid ?? (string) Str::uuid(),
            'source_type' => $sourceType,
            'source_action' => $sourceAction,
            'validation_type' => $validationType,
            'field_name' => $options['field_name'] ?? null,
            'error_message' => $errorMessage,
            'error_code' => $options['error_code'] ?? null,
            'input_data' => $options['input_data'] ?? null,
            'severity' => $options['severity'] ?? 'warning',
        ]);

        // Also log to file for debugging
        Log::warning("[VALIDATION FAILED] {$sourceType}.{$sourceAction}: {$errorMessage}", [
            'validation_type' => $validationType,
            'user_id' => auth()->id(),
            'input_data' => $options['input_data'] ?? null,
        ]);

        return $log;
    }

    /**
     * Log duplicate product error
     */
    public static function logDuplicate(
        string $sourceType,
        string $sourceAction,
        string $productName,
        int $row,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'duplicate',
            errorMessage: "Produk '{$productName}' duplikat di baris {$row}",
            options: [
                'field_name' => 'items_temp',
                'error_code' => 'VALIDATION_DUPLICATE_PRODUCT',
                'input_data' => $inputData,
                'severity' => 'warning',
            ]
        );
    }

    /**
     * Log stock insufficient error
     */
    public static function logStock(
        string $sourceType,
        string $sourceAction,
        string $productName,
        int $available,
        int $requested,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'stock',
            errorMessage: "Stok tidak cukup untuk {$productName}. Tersedia: {$available}, Diminta: {$requested}",
            options: [
                'field_name' => 'qty',
                'error_code' => 'VALIDATION_INSUFFICIENT_STOCK',
                'input_data' => array_merge($inputData, [
                    'stock_available' => $available,
                    'stock_requested' => $requested,
                ]),
                'severity' => 'error',
            ]
        );
    }

    /**
     * Log required field error
     */
    public static function logRequired(
        string $sourceType,
        string $sourceAction,
        string $fieldName,
        string $fieldLabel,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'required',
            errorMessage: "Field '{$fieldLabel}' wajib diisi",
            options: [
                'field_name' => $fieldName,
                'error_code' => 'VALIDATION_REQUIRED',
                'input_data' => $inputData,
                'severity' => 'warning',
            ]
        );
    }

    /**
     * Log format error
     */
    public static function logFormat(
        string $sourceType,
        string $sourceAction,
        string $fieldName,
        string $message,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'format',
            errorMessage: $message,
            options: [
                'field_name' => $fieldName,
                'error_code' => 'VALIDATION_FORMAT',
                'input_data' => $inputData,
                'severity' => 'warning',
            ]
        );
    }

    /**
     * Log business rule error
     */
    public static function logBusinessRule(
        string $sourceType,
        string $sourceAction,
        string $ruleName,
        string $message,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'business_rule',
            errorMessage: $message,
            options: [
                'error_code' => 'BUSINESS_RULE_'.strtoupper($ruleName),
                'input_data' => $inputData,
                'severity' => 'error',
            ]
        );
    }

    /**
     * Log minimum items error
     */
    public static function logMinimumItems(
        string $sourceType,
        string $sourceAction,
        int $minRequired,
        int $currentCount,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'minimum_items',
            errorMessage: "Minimal {$minRequired} item, saat ini hanya {$currentCount}",
            options: [
                'field_name' => 'items',
                'error_code' => 'VALIDATION_MINIMUM_ITEMS',
                'input_data' => array_merge($inputData, [
                    'min_required' => $minRequired,
                    'current_count' => $currentCount,
                ]),
                'severity' => 'warning',
            ]
        );
    }

    /**
     * Log batch not found error
     */
    public static function logBatchNotFound(
        string $sourceType,
        string $sourceAction,
        int $batchId,
        array $inputData = []
    ): ValidationLog {
        return self::log(
            sourceType: $sourceType,
            sourceAction: $sourceAction,
            validationType: 'batch_not_found',
            errorMessage: "Batch pembelian tidak ditemukan (ID: {$batchId})",
            options: [
                'field_name' => 'id_pembelian_item',
                'error_code' => 'VALIDATION_BATCH_NOT_FOUND',
                'input_data' => array_merge($inputData, [
                    'batch_id' => $batchId,
                ]),
                'severity' => 'error',
            ]
        );
    }

    /**
     * Get statistics
     */
    public static function getStats(int $days = 30): array
    {
        $query = ValidationLog::where('created_at', '>=', now()->subDays($days));

        return [
            'total' => $query->count(),
            'unresolved' => $query->clone()->unresolved()->count(),
            'by_type' => $query->clone()
                ->selectRaw('validation_type, COUNT(*) as count')
                ->groupBy('validation_type')
                ->pluck('count', 'validation_type')
                ->toArray(),
            'by_source' => $query->clone()
                ->selectRaw('source_type, COUNT(*) as count')
                ->groupBy('source_type')
                ->pluck('count', 'source_type')
                ->toArray(),
            'critical_count' => $query->clone()->where('severity', 'critical')->count(),
            'error_count' => $query->clone()->where('severity', 'error')->count(),
            'warning_count' => $query->clone()->where('severity', 'warning')->count(),
        ];
    }

    /**
     * Get recent logs
     */
    public static function getRecent(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ValidationLog::recent()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clear old logs
     */
    public static function cleanup(int $days = 90): int
    {
        return ValidationLog::where('created_at', '<', now()->subDays($days))
            ->where('is_resolved', true)
            ->delete();
    }

    /**
     * Mark all logs in a batch as resolved
     */
    public static function resolveBatch(string $uuid, ?string $notes = null): int
    {
        $userId = auth()->id();

        return ValidationLog::where('uuid', $uuid)
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolved_by' => $userId,
                'resolution_notes' => $notes,
            ]);
    }
}
