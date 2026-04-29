# ValidationLog Resource Refactor Plan

> Source analysis: `/home/galih/.gemini/antigravity/brain/40f52192-627d-49f9-b0cc-abc383aa616e/validation_log_resource_analysis.md.resolved`

## Overview

Fix 6 identified issues (4 performance, 2 bugs) in `ValidationLogResource.php`, `ValidationLog.php` model, and `ListValidationLogs.php`. The refactor introduces PHP backed Enums (following project conventions), fixes N+1 patterns, and eliminates index-defeating queries.

---

## Phase 1 — Create Backed Enums

### 1.1 Create `app/Enums/Severity.php`

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Severity: string implements HasLabel, HasColor, HasIcon
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Info => 'Info',
            self::Warning => 'Peringatan',
            self::Error => 'Error',
            self::Critical => 'Kritis',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Info => 'info',
            self::Warning => 'warning',
            self::Error => 'danger',
            self::Critical => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Info => 'heroicon-o-information-circle',
            self::Warning => 'heroicon-o-exclamation-triangle',
            self::Error => 'heroicon-o-x-circle',
            self::Critical => 'heroicon-o-fire',
        };
    }
}
```

### 1.2 Create `app/Enums/ValidationType.php`

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ValidationType: string implements HasLabel, HasColor
{
    case Duplicate = 'duplicate';
    case Stock = 'stock';
    case Required = 'required';
    case Format = 'format';
    case BusinessRule = 'business_rule';
    case MinimumItems = 'minimum_items';
    case BatchNotFound = 'batch_not_found';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Duplicate => 'Duplikat',
            self::Stock => 'Stok',
            self::Required => 'Wajib Diisi',
            self::Format => 'Format',
            self::BusinessRule => 'Aturan Bisnis',
            self::MinimumItems => 'Minimum Item',
            self::BatchNotFound => 'Batch Tidak Ditemukan',
        };
    }

    public function getColor(): string|array|null
    {
        return 'warning';
    }
}
```

### 1.3 Create `app/Enums/SourceType.php`

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SourceType: string implements HasLabel, HasColor
{
    case Penjualan = 'Penjualan';
    case Pembelian = 'Pembelian';
    case TukarTambah = 'TukarTambah';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Penjualan => 'Penjualan',
            self::Pembelian => 'Pembelian',
            self::TukarTambah => 'Tukar Tambah',
        };
    }

    public function getColor(): string|array|null
    {
        return 'primary';
    }
}
```

### 1.4 Update `app/Models/ValidationLog.php`

Changes:
- Add `use App\Enums\Severity;`, `use App\Enums\ValidationType;`, `use App\Enums\SourceType;`
- Update `$casts` array:
  ```php
  protected $casts = [
      'input_data' => 'array',
      'is_resolved' => 'boolean',
      'resolved_at' => 'datetime',
      'created_at' => 'datetime',
      'severity' => Severity::class,
      'validation_type' => ValidationType::class,
      'source_type' => SourceType::class,
  ];
  ```
- **Remove** `getSeverityColor()` method — replaced by `Severity::getColor()`
- **Remove** `getValidationTypeLabel()` method — replaced by `ValidationType::getLabel()`

### 1.5 Refactor `app/Filament/Resources/ValidationLogResource.php` — Use Enums

All inline `match()` blocks replaced with enum method calls:

**Infolist `severity` column (lines 72-87):**
```php
// BEFORE:
->color(fn ($state) => match ($state) { 'info' => 'info', ... })
->icon(fn ($state) => match ($state) { 'info' => 'heroicon-o-information-circle', ... })

// AFTER:
->color(fn (Severity $state) => $state->getColor())
->icon(fn (Severity $state) => $state->getIcon())
```

**Infolist `validation_type` column (lines 96-105):**
```php
// BEFORE:
->formatStateUsing(fn ($state) => match ($state) { 'duplicate' => 'Duplikat', ... })

// AFTER:
->formatStateUsing(fn (ValidationType $state) => $state->getLabel())
```

**Table `severity` column (lines 235-254):**
```php
// BEFORE:
->color(fn ($state) => match ($state) { ... })
->icon(fn ($state) => match ($state) { ... })
->formatStateUsing(fn ($state) => match ($state) { ... })

// AFTER:
->color(fn (Severity $state) => $state->getColor())
->icon(fn (Severity $state) => $state->getIcon())
->formatStateUsing(fn (Severity $state) => $state->getLabel())
```

**Table `validation_type` column (lines 268-277):**
```php
// BEFORE:
->formatStateUsing(fn ($state) => match ($state) { ... })

// AFTER:
->formatStateUsing(fn (ValidationType $state) => $state->getLabel())
```

**Filter `validation_type` options (lines 314-323):**
```php
// BEFORE:
->options([
    'duplicate' => 'Duplikat',
    'stock' => 'Stok',
    ...
])

// AFTER:
->options(ValidationType::class)
```
Filament automatically uses the enum's `getLabel()` for options when you pass an enum class.

**Filter `severity` options (lines 327-333):**
```php
// AFTER:
->options(Severity::class)
```

**Filter `source_type` options (lines 305-310):**
```php
// AFTER:
->options(SourceType::class)
```

**Infolist `method` color (lines 194-200):**
Keep inline — this is a simple HTTP method match, not a domain enum. Only 4 values, no reuse risk.

---

## Phase 2 — Fix Performance Issues

### 2a. Memoize Navigation Badge Count

In `ValidationLogResource.php`, replace the two separate queries with a shared static cache:

```php
protected static ?int $unresolvedCountCache = null;

public static function getNavigationBadge(): ?string
{
    if (static::$unresolvedCountCache === null) {
        static::$unresolvedCountCache = static::getModel()::where('is_resolved', false)->count();
    }

    return static::$unresolvedCountCache > 0 ? (string) static::$unresolvedCountCache : null;
}

public static function getNavigationBadgeColor(): ?string
{
    if (static::$unresolvedCountCache === null) {
        static::$unresolvedCountCache = static::getModel()::where('is_resolved', false)->count();
    }

    return static::$unresolvedCountCache > 0 ? 'danger' : 'success';
}
```

### 2b. Mass Update for Bulk Resolve

In `ValidationLogResource.php`, replace the foreach loop (lines 410-424):

```php
// BEFORE:
->action(function ($records) {
    $count = 0;
    foreach ($records as $record) {
        if (! $record->is_resolved) {
            $record->markAsResolved('Diselesaikan secara massal');
            $count++;
        }
    }
    ...
})

// AFTER:
->action(function ($records) {
    $unresolvedIds = $records->where('is_resolved', false)->pluck('id');
    $count = $unresolvedIds->count();

    if ($count > 0) {
        ValidationLog::whereIn('id', $unresolvedIds)->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution_notes' => 'Diselesaikan secara massal',
        ]);
    }

    \Filament\Notifications\Notification::make()
        ->title("{$count} log validasi berhasil ditandai selesai")
        ->icon('heroicon-o-check-circle')
        ->success()
        ->send();
})
```

### 2c. Fix `whereDate` Index Defeat

In the `created_at` filter query (lines 349-351), replace `whereDate` with Carbon-based range:

```php
// BEFORE:
->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
->when($data['to'], fn ($q) => $q->whereDate('created_at', '<=', $data['to']))

// AFTER:
->when($data['from'], fn ($q) => $q->where('created_at', '>=', \Carbon\Carbon::parse($data['from'])->startOfDay()))
->when($data['to'], fn ($q) => $q->where('created_at', '<=', \Carbon\Carbon::parse($data['to'])->endOfDay()))
```

This allows MySQL to use the `idx_created_at` index.

### 2d. Adjust Polling Interval

```php
// BEFORE:
->poll('30s')

// AFTER:
->poll('60s')
```

Logs don't change rapidly enough to warrant 30s polling. 60s is sufficient.

---

## Phase 3 — Bug Fixes

### 3.1 Null-Safe Tooltips

In `ValidationLogResource.php`:

```php
// Line 282 — error_message tooltip:
// BEFORE:
->tooltip(fn ($record) => $record->error_message)
// AFTER:
->tooltip(fn ($record) => $record->error_message ?? '-')

// Line 210 — user_agent tooltip:
// BEFORE:
->tooltip(fn ($record) => $record->user_agent)
// AFTER:
->tooltip(fn ($record) => $record->user_agent ?? '-')
```

### 3.2 Fix ListValidationLogs Tab Badge Queries

In `app/Filament/Resources/ValidationLogResource/Pages/ListValidationLogs.php`, cache the counts:

```php
public function getTabs(): array
{
    $total = ValidationLog::count();
    $unresolved = ValidationLog::where('is_resolved', false)->count();
    $resolved = $total - $unresolved;
    $critical = ValidationLog::whereIn('severity', ['error', 'critical'])
        ->where('is_resolved', false)
        ->count();

    return [
        'semua' => Tab::make('Semua')
            ->icon('heroicon-o-list-bullet')
            ->badge($total)
            ->badgeColor('gray'),

        'belum_selesai' => Tab::make('Belum Selesai')
            ->icon('heroicon-o-clock')
            ->badge($unresolved)
            ->badgeColor('warning')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', false)),

        'selesai' => Tab::make('Sudah Selesai')
            ->icon('heroicon-o-check-circle')
            ->badge($resolved)
            ->badgeColor('success')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('is_resolved', true)),

        'kritis' => Tab::make('Kritis & Error')
            ->icon('heroicon-o-fire')
            ->badge($critical)
            ->badgeColor('danger')
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('severity', ['error', 'critical'])->where('is_resolved', false)),
    ];
}
```

This reduces 4 queries to 3 (total + unresolved + critical, with resolved computed from total-unresolved).

**Note:** After deploying Severity enum, the `whereIn('severity', ['error', 'critical'])` needs to become `whereIn('severity', [Severity::Error->value, Severity::Critical->value])`.

---

## Files to Modify Summary

| File | Action |
|------|--------|
| `app/Enums/Severity.php` | CREATE |
| `app/Enums/ValidationType.php` | CREATE |
| `app/Enums/SourceType.php` | CREATE |
| `app/Models/ValidationLog.php` | EDIT — add enum casts, remove helper methods |
| `app/Filament/Resources/ValidationLogResource.php` | EDIT — use enums, fix perf bugs, fix null tooltips |
| `app/Filament/Resources/ValidationLogResource/Pages/ListValidationLogs.php` | EDIT — optimize tab badge queries |

## Risk Assessment

- **Enum cast migration**: Existing database values ('info', 'warning', 'error', 'critical', 'duplicate', etc.) already match the enum `value` properties, so no data migration needed. The enum casting will work seamlessly.
- **Bulk action change**: Mass update bypasses Eloquent events (no `updated` model event fired). Since `ValidationLog` doesn't have observers or model events, this is safe.
- **Tooltip fallback**: Minimal risk — just defensive coding.

---

## Phase 4 — Fix `created_at` NULL Bug (Critical)

### Root Cause

All 17 records in `validation_logs` have `created_at = NULL`. Two problems:

1. **`created_at` is NOT in `$fillable`** — The `log()` method passes `'created_at' => now()`, but since it's not in `$fillable` and the model uses whitelist protection, Laravel silently discards the value during mass assignment.
2. **`$timestamps = false`** — Laravel doesn't auto-set `created_at`/`updated_at` since this is disabled.

### Fix 4.1 — Add `created_at` to `$fillable`

In `app/Models/ValidationLog.php`, add `'created_at'` to the `$fillable` array:

```php
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
    'created_at',  // <-- ADD THIS
];
```

### Fix 4.2 — Add `boot()` method to auto-set `created_at`

Even with `created_at` in `$fillable`, we should add a `creating` event to guarantee it's always set, so `ValidationLog::create()` calls (not just `log()`) also work:

```php
protected static function booted(): void
{
    static::creating(function (ValidationLog $log) {
        if (empty($log->created_at)) {
            $log->created_at = now();
        }
    });
}
```

Add this method right after the `$casts` property in the model.

### Fix 4.3 — Create migration to backfill existing NULL `created_at`

Run: `php artisan make:migration backfill_validation_logs_created_at`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('validation_logs')
            ->whereNull('created_at')
            ->update(['created_at' => now()]);
    }

    public function down(): void
    {
        // No rollback — we can't recover original timestamps
    }
};
```

### Files to Modify (Phase 4)

| File | Action |
|------|--------|
| `app/Models/ValidationLog.php` | EDIT — add `created_at` to `$fillable`, add `booted()` method |
| `database/migrations/YYYY_MM_DD_HHMMSS_backfill_validation_logs_created_at.php` | CREATE — backfill NULL timestamps |