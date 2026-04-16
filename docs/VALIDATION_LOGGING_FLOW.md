# Sistem Logging untuk Validasi Gagal

## Tujuan
Mencatat setiap validasi yang gagal agar:
1. Mudah dilacak masalah input dari user
2. Analisis pattern error validasi
3. Debug lebih cepat
4. Audit trail untuk keamanan

## Flow Logging Validasi Gagal

```
┌─────────────────────────────────────────────────────────────┐
│                    USER SUBMIT FORM                          │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────────────────────┐
│              TRIGGER VALIDATION (Form/Page)                  │
│  - Cek duplikat produk                                       │
│  - Cek stok tersedia                                         │
│  - Cek required fields                                       │
│  - Cek business rules                                        │
└──────────────────┬──────────────────────────────────────────┘
                   │
                   ▼
         ┌─────────────────┐
         │  VALIDASI LOLIS? │
         └────────┬────────┘
                  │
       ┌──────────┴──────────┐
       │                     │
      YES                   NO
       │                     │
       ▼                     ▼
┌──────────┐    ┌──────────────────────────────────┐
│  SIMPAN  │    │      LOG VALIDASI GAGAL           │
│  DATA    │    │  - Simpan ke validation_logs     │
└──────────┘    │  - Kirim notifikasi toast         │
                │  - Kirim notifikasi database      │
                │  - Tampilkan error di form        │
                └──────────────────────────────────┘
```

## Struktur Tabel validation_logs

```sql
CREATE TABLE validation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Identitas
    uuid CHAR(36) NOT NULL, -- untuk grouping batch validation
    
    -- Konteks
    source_type VARCHAR(50) NOT NULL, -- 'Penjualan', 'TukarTambah', 'Pembelian'
    source_action VARCHAR(50) NOT NULL, -- 'create', 'update', 'delete'
    
    -- User Info
    user_id BIGINT UNSIGNED NULL,
    user_name VARCHAR(255) NULL,
    
    -- Error Detail
    validation_type VARCHAR(50) NOT NULL, -- 'duplicate', 'stock', 'required', 'format'
    field_name VARCHAR(100) NULL, -- 'items_temp', 'qty', 'id_produk'
    error_message TEXT NOT NULL,
    error_code VARCHAR(50) NULL, -- VALIDATION_DUPLICATE, VALIDATION_STOCK, etc
    
    -- Input Data (yang menyebabkan error)
    input_data JSON NULL, -- snapshot data yang diinput
    
    -- Context
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    url TEXT NULL,
    method VARCHAR(10) NULL,
    
    -- Severity
    severity VARCHAR(20) DEFAULT 'warning', -- 'info', 'warning', 'error', 'critical'
    
    -- Resolution
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by BIGINT UNSIGNED NULL,
    resolution_notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_source (source_type, source_action),
    INDEX idx_user (user_id),
    INDEX idx_validation_type (validation_type),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    INDEX idx_uuid (uuid),
    INDEX idx_is_resolved (is_resolved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Model: ValidationLog

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ValidationLog extends Model
{
    protected $table = 'validation_logs';
    
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
    
    public static function log(array $data): self
    {
        return self::create([
            'uuid' => $data['uuid'] ?? (string) Str::uuid(),
            'source_type' => $data['source_type'],
            'source_action' => $data['source_action'],
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'Guest',
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
        ]);
    }
    
    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }
    
    public function scopeBySource($query, string $source)
    {
        return $query->where('source_type', $source);
    }
    
    public function scopeByType($query, string $type)
    {
        return $query->where('validation_type', $type);
    }
    
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    // Methods
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
```

## Service: ValidationLogger

```php
<?php

namespace App\Services;

use App\Models\ValidationLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
                'field_name' => 'items',
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
                'error_code' => 'BUSINESS_RULE_' . strtoupper($ruleName),
                'input_data' => $inputData,
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
        ];
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
}
```

## Integration dengan CreatePenjualan

```php
// Di CreatePenjualan.php - validateBeforeCreate()
protected function validateBeforeCreate(array $items): void
{
    // Start batch logging
    $batchUuid = ValidationLogger::startBatch();
    
    $hasError = false;
    
    // Cek duplikat
    $productKeys = [];
    foreach ($items as $index => $item) {
        $productId = (int) ($item['id_produk'] ?? 0);
        $condition = $item['kondisi'] ?? null;
        $batchId = (int) ($item['id_pembelian_item'] ?? 0);
        
        if ($productId > 0) {
            $key = $productId.'|'.($condition ?? '').'|'.$batchId;
            
            if (isset($productKeys[$key])) {
                $productName = Produk::find($productId)?->nama_produk ?? 'Produk #'.$productId;
                
                // LOG VALIDATION ERROR
                ValidationLogger::logDuplicate(
                    sourceType: 'Penjualan',
                    sourceAction: 'create',
                    productName: $productName,
                    row: $index + 1,
                    inputData: [
                        'product_id' => $productId,
                        'batch_id' => $batchId,
                        'condition' => $condition,
                        'duplicate_row' => $productKeys[$key],
                        'current_row' => $index + 1,
                    ]
                );
                
                $hasError = true;
                
                // ... throw exception
            }
            $productKeys[$key] = $index + 1;
        }
    }
    
    // Cek stok
    foreach ($totalQtyMap as $group) {
        if ($availableQty < $requestedQty) {
            // LOG VALIDATION ERROR
            ValidationLogger::logStock(
                sourceType: 'Penjualan',
                sourceAction: 'create',
                productName: $productName,
                available: $availableQty,
                requested: $requestedQty,
                inputData: [
                    'product_id' => $productId,
                    'batch_id' => $batchId,
                    'rows' => $rows,
                ]
            );
            
            $hasError = true;
            
            // ... throw exception
        }
    }
    
    // Jika ada error, log batch summary
    if ($hasError) {
        Log::info("[VALIDATION BATCH] Batch {$batchUuid} has validation errors");
    }
}
```

## Filament Resource: ValidationLogResource

```php
<?php

namespace App\Filament\Resources;

use App\Models\ValidationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ValidationLogResource extends Resource
{
    protected static ?string $model = ValidationLog::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Validation Logs';
    protected static ?int $navigationSort = 99;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('source_type')
                    ->disabled(),
                Forms\Components\TextInput::make('validation_type')
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('input_data')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_resolved')
                    ->label('Resolved'),
                Forms\Components\Textarea::make('resolution_notes')
                    ->label('Resolution Notes'),
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'success' => 'info',
                        'warning' => 'warning',
                        'danger' => 'error',
                        'danger' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('validation_type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('User'),
                Tables\Columns\IconColumn::make('is_resolved')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        'Penjualan' => 'Penjualan',
                        'Pembelian' => 'Pembelian',
                        'TukarTambah' => 'Tukar Tambah',
                    ]),
                Tables\Filters\SelectFilter::make('validation_type')
                    ->options([
                        'duplicate' => 'Duplicate',
                        'stock' => 'Stock',
                        'required' => 'Required',
                        'format' => 'Format',
                        'business_rule' => 'Business Rule',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'error' => 'Error',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Resolved'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('created_at', '<=', $data['to']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! $record->is_resolved)
                    ->action(fn ($record) => $record->markAsResolved()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('resolve')
                    ->label('Mark Resolved')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->markAsResolved()),
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidationLogs::route('/'),
            'view' => Pages\ViewValidationLog::route('/{record}'),
        ];
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
}
```

## Dashboard Widget: ValidationStatsWidget

```php
<?php

namespace App\Filament\Widgets;

use App\Models\ValidationLog;
use App\Services\ValidationLogger;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ValidationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = ValidationLogger::getStats(7); // Last 7 days
        
        return [
            Stat::make('Total Validation Errors', $stats['total'])
                ->description('Last 7 days')
                ->descriptionIcon('heroicon-m-arrow-trend-up')
                ->color('danger'),
                
            Stat::make('Unresolved', $stats['unresolved'])
                ->description('Needs attention')
                ->color('warning'),
                
            Stat::make('Critical', $stats['critical_count'])
                ->description('High priority')
                ->color('danger'),
                
            Stat::make('Most Common', collect($stats['by_type'])->keys()->first() ?? 'None')
                ->description('Top error type')
                ->color('info'),
        ];
    }
}
```

## Cron Job untuk Cleanup

```php
// app/Console/Commands/CleanupValidationLogs.php
<?php

namespace App\Console\Commands;

use App\Services\ValidationLogger;
use Illuminate\Console\Command;

class CleanupValidationLogs extends Command
{
    protected $signature = 'validation-logs:cleanup {--days=90 : Days to keep}';
    protected $description = 'Cleanup old resolved validation logs';
    
    public function handle(): int
    {
        $days = $this->option('days');
        $count = ValidationLogger::cleanup($days);
        
        $this->info("Deleted {$count} old validation logs");
        
        return self::SUCCESS;
    }
}

// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('validation-logs:cleanup')->weekly();
}
```

## Summary Implementasi

### Files yang perlu dibuat:
1. Migration: `create_validation_logs_table`
2. Model: `ValidationLog`
3. Service: `ValidationLogger`
4. Resource: `ValidationLogResource` + Pages
5. Widget: `ValidationStatsWidget`
6. Command: `CleanupValidationLogs`

### Files yang perlu dimodifikasi:
1. `CreatePenjualan.php` - tambah logging di validateBeforeCreate
2. `EditPenjualan.php` - tambah logging di validateBeforeSave
3. `CreateTukarTambah.php` - tambah logging di validatePenjualanItems
4. `EditTukarTambah.php` - tambah logging di validatePenjualanItems

Mau saya implementasikan sekarang?
