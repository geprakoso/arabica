# 🗺️ Roadmap Upgrade Modul Penjualan

> Dokumen ini adalah urutan kerja terstruktur untuk memperbaiki bug yang ada dan meng-upgrade modul Penjualan sesuai business rules baru.

---

## 📊 Klasifikasi: FIX vs UPGRADE

### 🔧 FIX (Perbaikan Bug — Tanpa Mengubah Business Rules)

| # | Bug | Tingkat | File Terdampak |
|---|-----|---------|----------------|
| F1 | StockBatch tidak di-update saat penjualan | 🔴 Critical | `PenjualanItem.php` |
| F2 | Tidak ada pessimistic locking | 🔴 High | `PenjualanItem.php` |
| F3 | Tidak ada audit trail (StockMutation) | 🟠 Medium | `PenjualanItem.php` |
| F4 | Logic update tidak atomic | 🔴 High | `PenjualanItem.php` |
| F5 | Validasi stok pakai `qty_sisa` | 🔴 High | `PenjualanItem.php` |
| F6 | UI display pakai `qty_sisa` | 🟠 Medium | `PenjualanResource.php` |
| F7 | `max(0, ...)` silent bug | 🟠 Medium | `PenjualanItem.php` |
| F8 | Observer hanya sync WooCommerce | 🟠 Medium | `PembelianItemObserver.php` |

### 🚀 UPGRADE (Fitur & Business Rules Baru)

| # | Fitur | File Terdampak |
|---|-------|----------------|
| U1 | Draft & Final state machine | `Penjualan.php`, migration |
| U2 | Tab Draft / Final | `PenjualanResource.php` |
| U3 | Lock items/jasa setelah save | `PenjualanResource.php` |
| U4 | Void to Draft (1x) | `Penjualan.php` |
| U5 | Lock Final (permanent) | `Penjualan.php` |
| U6 | Hapus Final/Lock (stok kembali) | `Penjualan.php` |
| U7 | Status Pembayaran TEMPO/LUNAS | `Penjualan.php` |
| U8 | SN & Garansi validasi | `PenjualanItem.php` |
| U9 | Jasa filter nota already-out | `PenjualanJasa.php`, `PenjualanResource.php` |
| U10 | Delete block kalau ada TT | `Penjualan.php` |

---

## 🎯 Urutan Phase

**Prinsip:** Fix fondasi dulu, baru bangun fitur di atasnya.

```
PHASE 0: HOTFIX SEMENTARA (Production Only — Skip jika dev)
   └── Sync qty_sisa → StockBatch via observer (sementara)

PHASE 1: FIX FOUNDATION (Bugfix Critical)
   └── Refactor PenjualanItem ke StockBatch + Locking + Audit

PHASE 2: UPGRADE STRUCTURE (Migration & Model)
   └── Draft/Final columns + State machine methods

PHASE 3: UPGRADE UI (Filament Resource)
   └── Tab Draft/Final + Lock behavior + Tombol Post/Void/Lock

PHASE 4: UPGRADE FEATURES (Business Rules)
   └── SN, Jasa, Payment, Delete validation

PHASE 5: INTEGRATION TESTING (End-to-End)
   └── Race condition, full flow, cleanup
```

---

## 🔥 PHASE 0: HOTFIX SEMENTARA (Optional)

> **Skip jika masih development.** Lakukan hanya jika production butuh stabilitas sementara.

### 0.1 Observer Sync qty_sisa → StockBatch

**File:** `app/Observers/PembelianItemObserver.php`

```php
public function updated(PembelianItem $item): void
{
    if ($item->wasChanged('qty_sisa')) {
        // HOTFIX: Sync ke StockBatch
        $batch = $item->stockBatch;
        if ($batch) {
            $batch->update(['qty_available' => $item->qty_sisa]);
        }
        
        // WooCommerce sync tetap
        $this->dispatchSyncIfNeeded($item, 'updated');
    }
}
```

### 0.2 Jalankan Sync Command

```bash
php artisan inventory:sync-stock-batch
```

### ✅ Testing
- [ ] `test('observer sync qty_sisa ke stock_batch')`

---

## 🔥 PHASE 1: FIX FOUNDATION (Bugfix Critical)

**Goal:** Penjualan pakai `StockBatch` sebagai single source of truth untuk stok.

### 1.1 Refactor `PenjualanItem::applyStockMutation()`

**File:** `app/Models/PenjualanItem.php`

**Ubah dari:**
```php
protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
{
    $qtyColumn = PembelianItem::qtySisaColumn();
    $batch = PembelianItem::query()->find($batchId);
    $updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
    $batch->{$qtyColumn} = $updatedQty;
    $batch->save();  // ❌ Hanya update qty_sisa
}
```

**Ubah jadi:**
```php
protected static function applyStockMutation(PenjualanItem $item, int $qtyDelta): void
{
    $batch = StockBatch::where('pembelian_item_id', $item->id_pembelian_item)->first();
    
    if (! $batch) {
        throw new \RuntimeException("StockBatch tidak ditemukan untuk PembelianItem #{$item->id_pembelian_item}");
    }
    
    if ($qtyDelta < 0) {
        // Jual / Deduct
        StockBatch::decrementWithLock(
            $batch->id,
            abs($qtyDelta),
            [
                'type' => 'sale',
                'reference_type' => 'PenjualanItem',
                'reference_id' => $item->id_penjualan_item,
                'notes' => "Penjualan: {$item->qty} unit",
            ]
        );
    } elseif ($qtyDelta > 0) {
        // Return / Cancel / Delete
        StockBatch::incrementWithLock(
            $batch->id,
            $qtyDelta,
            [
                'type' => 'sale_return',
                'reference_type' => 'PenjualanItem',
                'reference_id' => $item->id_penjualan_item,
                'notes' => "Return/Cancel: {$qtyDelta} unit",
            ]
        );
    }
}
```

### 1.2 Refactor `PenjualanItem::assertStockAvailable()`

**File:** `app/Models/PenjualanItem.php`

**Ubah dari:**
```php
$qtyColumn = PembelianItem::qtySisaColumn();
$availableQty = (int) ($batch->{$qtyColumn} ?? 0);  // ❌ qty_sisa
```

**Ubah jadi:**
```php
$stockBatch = StockBatch::where('pembelian_item_id', $batchId)->first();
$availableQty = $stockBatch ? $stockBatch->qty_available : 0;  // ✅ StockBatch
```

### 1.3 Refactor Event Listener (Atomic Transaction)

**File:** `app/Models/PenjualanItem.php`

**Ubah `created()` event:**
```php
static::created(function (PenjualanItem $item): void {
    DB::transaction(function () use ($item) {
        self::applyStockMutation($item, -1 * (int) $item->qty);
        $item->penjualan->recalculateTotals();
    });
});
```

**Ubah `updated()` event:**
```php
static::updated(function (PenjualanItem $item): void {
    DB::transaction(function () use ($item) {
        $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
        $originalQty = (int) $item->getOriginal('qty');
        $newBatchId = (int) $item->id_pembelian_item;
        $newQty = (int) $item->qty;
        
        // Kembalikan stok lama
        if ($originalBatchId) {
            $originalItem = clone $item;
            $originalItem->id_pembelian_item = $originalBatchId;
            $originalItem->qty = $originalQty;
            self::applyStockMutation($originalItem, $originalQty);
        }
        
        // Kurangi stok baru
        self::applyStockMutation($item, -1 * $newQty);
        
        $item->penjualan->recalculateTotals();
    });
});
```

**Ubah `deleted()` event:**
```php
static::deleted(function (PenjualanItem $item): void {
    DB::transaction(function () use ($item) {
        self::applyStockMutation($item, (int) $item->qty);  // Kembalikan stok
        $item->penjualan->recalculateTotals();
    });
});
```

### 1.4 Refactor UI Display (StockBatch)

**File:** `app/Filament/Resources/PenjualanResource.php`

**Ubah `getBatchOptions()`:**
```php
public static function getBatchOptions(?int $productId, ?string $condition = null): array
{
    $batches = StockBatch::query()
        ->whereHas('pembelianItem', fn ($q) => $q->where('id_produk', $productId))
        ->where('qty_available', '>', 0)  // ✅ StockBatch
        ->whereDoesntHave('pembelianItem.rmas', fn ($q) => $q->whereIn('status', Rma::activeStatuses()))
        ->with('pembelianItem')
        ->get();
    
    return $batches->mapWithKeys(function ($batch) {
        $item = $batch->pembelianItem;
        $label = "{$item->pembelian->no_nota} | {$item->kondisi} | Qty: {$batch->qty_available}";
        return [$item->id_pembelian_item => $label];
    })->toArray();
}
```

**Ubah `getAvailableQty()`:**
```php
public static function getAvailableQty(int $productId, ?string $condition = null): int
{
    return (int) StockBatch::query()
        ->whereHas('pembelianItem', function ($q) use ($productId, $condition) {
            $q->where('id_produk', $productId);
            if ($condition) {
                $q->where('kondisi', $condition);
            }
        })
        ->whereDoesntHave('pembelianItem.rmas', fn ($q) => $q->whereIn('status', Rma::activeStatuses()))
        ->sum('qty_available');  // ✅ StockBatch
}
```

### 1.5 Fix `max(0, ...)` Silent Bug

**File:** `app/Models/PenjualanItem.php`

Hapus `max(0, ...)` dan biarkan `StockBatch::decrementWithLock()` yang handle validasi (sudah throw exception kalau stok tidak cukup).

### 1.6 Update Observer (Hapus Hotfix)

**File:** `app/Observers/PembelianItemObserver.php`

Hapus hotfix sync qty_sisa → StockBatch (sudah tidak perlu karena Penjualan tidak lagi sentuh qty_sisa).

### ✅ Testing Phase 1

```php
// File: tests/Unit/SalesModule/StockBatchIntegrationTest.php

test('penjualan mengurangi stock_batch_qty_available', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan();
    createPenjualanItem($penjualan, $batch, 3);
    
    expect($batch->fresh()->qty_available)->toBe(7);
    expect(StockMutation::where('type', 'sale')->count())->toBe(1);
});

test('hapus penjualan_item mengembalikan stok ke batch', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batch, 3);
    
    $item->delete();
    
    expect($batch->fresh()->qty_available)->toBe(10);
    expect(StockMutation::where('type', 'sale_return')->count())->toBe(1);
});

test('update penjualan_item batch mengembalikan stok lama dan kurangi stok baru', function () {
    $batchA = createStockBatch(10);
    $batchB = createStockBatch(10);
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batchA, 3);
    
    $item->update(['id_pembelian_item' => $batchB->pembelian_item_id, 'qty' => 2]);
    
    expect($batchA->fresh()->qty_available)->toBe(10);  // kembali
    expect($batchB->fresh()->qty_available)->toBe(8);   // terdeduct
});

test('penjualan melebihi stok gagal dengan exception', function () {
    $batch = createStockBatch(5);
    $penjualan = createPenjualan();
    
    expect(fn () => createPenjualanItem($penjualan, $batch, 10))
        ->toThrow(\RuntimeException::class);
});

test('concurrent penjualan tidak oversell', function () {
    // Test race condition
});
```

**Estimasi:** 2 hari

---

## 🚀 PHASE 2: UPGRADE STRUCTURE (Migration & Model)

**Goal:** Tambah state machine Draft/Final ke model Penjualan.

### 2.1 Migration

**File:** `database/migrations/2026_04_30_000000_add_status_fields_to_penjualan.php`

```php
Schema::table('tb_penjualan', function (Blueprint $table) {
    $table->string('status_dokumen', 20)->default('draft')->after('status'); // draft | final
    $table->boolean('is_locked')->default(false)->after('status_dokumen');
    $table->boolean('void_used')->default(false)->after('is_locked');
    $table->timestamp('posted_at')->nullable()->after('void_used');
    $table->foreignId('posted_by_id')->nullable()->constrained('users')->after('posted_at');
    $table->timestamp('voided_at')->nullable()->after('posted_by_id');
    $table->foreignId('voided_by_id')->nullable()->constrained('users')->after('voided_at');
});
```

### 2.2 Backfill Existing Data

**File:** `database/migrations/2026_04_30_000001_backfill_penjualan_status.php`

```php
DB::table('tb_penjualan')->update([
    'status_dokumen' => 'final',
    'is_locked' => false,
    'void_used' => false,
]);
```

### 2.3 Update `Penjualan` Model

**File:** `app/Models/Penjualan.php`

```php
protected $fillable = [
    // ... existing
    'status_dokumen',
    'is_locked',
    'void_used',
    'posted_at',
    'posted_by_id',
    'voided_at',
    'voided_by_id',
];

protected $casts = [
    'is_locked' => 'boolean',
    'void_used' => 'boolean',
    'posted_at' => 'datetime',
    'voided_at' => 'datetime',
];

public function isDraft(): bool
{
    return $this->status_dokumen === 'draft';
}

public function isFinal(): bool
{
    return $this->status_dokumen === 'final';
}

public function canEditItems(): bool
{
    return $this->isDraft() 
        && ! $this->is_locked 
        && ! $this->items()->exists();
}

public function canEditJasa(): bool
{
    return $this->canEditItems();
}

public function canEditPayment(): bool
{
    return $this->isDraft() && ! $this->is_locked;
}

public function canVoid(): bool
{
    return $this->isFinal() 
        && ! $this->is_locked 
        && ! $this->void_used;
}

public function canPost(): bool
{
    return $this->isDraft() && ! $this->is_locked;
}

public function canLock(): bool
{
    return $this->isFinal() && ! $this->is_locked;
}

public function post(): void
{
    if (! $this->canPost()) {
        throw new \RuntimeException('Penjualan tidak bisa di-post.');
    }
    
    DB::transaction(function () {
        $this->update([
            'status_dokumen' => 'final',
            'posted_at' => now(),
            'posted_by_id' => auth()->id(),
        ]);
    });
}

public function voidToDraft(): void
{
    if (! $this->canVoid()) {
        throw new \RuntimeException('Penjualan tidak bisa di-void.');
    }
    
    $this->update([
        'status_dokumen' => 'draft',
        'void_used' => true,
        'voided_at' => now(),
        'voided_by_id' => auth()->id(),
    ]);
    // Stok TIDAK dikembalikan!
}

public function lockFinal(): void
{
    if (! $this->canLock()) {
        throw new \RuntimeException('Penjualan tidak bisa di-lock.');
    }
    
    $this->update(['is_locked' => true]);
}
```

### 2.4 Update `PenjualanItem` untuk Handle Draft

**File:** `app/Models/PenjualanItem.php`

Tambah logic: saat draft baru, catat mutation type='sale_draft'. Saat post, update jadi 'sale'.

```php
static::created(function (PenjualanItem $item): void {
    DB::transaction(function () use ($item) {
        $mutationType = $item->penjualan->isDraft() ? 'sale_draft' : 'sale';
        
        self::applyStockMutation($item, -1 * (int) $item->qty, $mutationType);
        $item->penjualan->recalculateTotals();
    });
});
```

### 2.5 Update Delete Behavior

**File:** `app/Models/Penjualan.php`

```php
public function canDelete(): bool
{
    if (filled($this->no_tt)) {
        return false;
    }
    return true;
}

public function delete(): ?bool
{
    if (! $this->canDelete()) {
        throw ValidationException::withMessages([
            'delete' => 'Penjualan tidak bisa dihapus karena terkait Tukar Tambah.'
        ]);
    }
    
    return DB::transaction(function () {
        // Kembalikan semua stok
        foreach ($this->items as $item) {
            StockBatch::incrementWithLock(
                $item->stockBatch->id,
                $item->qty,
                [
                    'type' => 'sale_return',
                    'reference_type' => 'Penjualan',
                    'reference_id' => $this->id,
                    'notes' => "Hapus penjualan: {$item->qty} unit",
                ]
            );
        }
        
        // Hapus mutations
        StockMutation::where('reference_type', 'Penjualan')
            ->where('reference_id', $this->id)
            ->delete();
        
        StockMutation::where('reference_type', 'PenjualanItem')
            ->whereIn('reference_id', $this->items->pluck('id_penjualan_item'))
            ->delete();
        
        return parent::delete();
    });
}
```

### ✅ Testing Phase 2

```php
// File: tests/Unit/SalesModule/StateMachineTest.php

test('penjualan baru status draft', function () {
    $penjualan = createPenjualan();
    expect($penjualan->isDraft())->toBeTrue();
    expect($penjualan->isFinal())->toBeFalse();
});

test('draft bisa di-post jadi final', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    $penjualan->post();
    
    expect($penjualan->fresh()->isFinal())->toBeTrue();
    expect($penjualan->fresh()->posted_at)->not->toBeNull();
});

test('final bisa di-void ke draft 1x', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    
    expect($penjualan->canVoid())->toBeTrue();
    
    $penjualan->voidToDraft();
    
    expect($penjualan->fresh()->isDraft())->toBeTrue();
    expect($penjualan->fresh()->void_used)->toBeTrue();
    expect($penjualan->fresh()->canVoid())->toBeFalse();
});

test('final bisa di-lock', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    
    expect($penjualan->canLock())->toBeTrue();
    
    $penjualan->lockFinal();
    
    expect($penjualan->fresh()->is_locked)->toBeTrue();
    expect($penjualan->fresh()->canLock())->toBeFalse();
});

test('draft baru bisa edit items dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    
    expect($penjualan->canEditItems())->toBeTrue();
    expect($penjualan->canEditJasa())->toBeTrue();
});

test('draft saved tidak bisa edit items dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, createStockBatch(10), 2);
    $penjualan->refresh();
    
    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeTrue();
});

test('final tidak bisa edit apapun', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    
    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeFalse();
});

test('draft hasil void hanya bisa edit payment', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    createPenjualanItem($penjualan, createStockBatch(10), 2);
    $penjualan->voidToDraft();
    $penjualan->refresh();
    
    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeTrue();
});

test('lock final mencegah semua edit dan void', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => true]);
    
    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeFalse();
    expect($penjualan->canVoid())->toBeFalse();
});

test('final posted bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->delete();
    
    expect($batch->fresh()->qty_available)->toBe(10);
    expect(StockMutation::where('reference_id', $penjualan->id)->count())->toBe(0);
});

test('final locked bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => true]);
    createPenjualanItem($penjualan, $batch, 3);
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->delete();
    
    expect($batch->fresh()->qty_available)->toBe(10);
});

test('draft hasil void bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();
    $penjualan->voidToDraft();
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->delete();
    
    expect($batch->fresh()->qty_available)->toBe(10);
});

test('stok tidak dikembalikan saat void', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->voidToDraft();
    
    expect($batch->fresh()->qty_available)->toBe(7); // TETAP!
});

test('tidak bisa hapus kalau ada tt', function () {
    $penjualan = createPenjualan(['no_tt' => 'TT-001']);
    
    expect($penjualan->canDelete())->toBeFalse();
    
    expect(fn () => $penjualan->delete())
        ->toThrow(ValidationException::class);
});
```

**Estimasi:** 2 hari

---

## 🖥️ PHASE 3: UPGRADE UI (Filament Resource)

**Goal:** Implementasi tab Draft/Final dan state machine di UI.

### 3.1 Tab Header Draft / Final

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
// Tabs di header list
public function getHeaderActions(): array
{
    return [
        Action::make('draft')
            ->label('Draft')
            ->url(fn () => static::getUrl('index', ['tab' => 'draft']))
            ->color(fn () => request()->query('tab', 'draft') === 'draft' ? 'primary' : 'gray'),
        Action::make('final')
            ->label('Final')
            ->url(fn () => static::getUrl('index', ['tab' => 'final']))
            ->color(fn () => request()->query('tab') === 'final' ? 'primary' : 'gray'),
    ];
}

// Query scope
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $tab = request()->query('tab', 'draft');
    return $query->where('status_dokumen', $tab);
}
```

### 3.2 Lock Items & Jasa Repeater

```php
Forms\Components\Repeater::make('items')
    ->label('Item Produk')
    ->disabled(fn (?Penjualan $record) => $record && ! $record->canEditItems())
    ->deletable(fn (?Penjualan $record) => $record && $record->canEditItems())
    ->addable(fn (?Penjualan $record) => $record && $record->canEditItems())
    ->schema([...])

Forms\Components\Repeater::make('jasaItems')
    ->label('Item Jasa')
    ->disabled(fn (?Penjualan $record) => $record && ! $record->canEditJasa())
    ->deletable(fn (?Penjualan $record) => $record && $record->canEditJasa())
    ->addable(fn (?Penjualan $record) => $record && $record->canEditJasa())
    ->schema([...])
```

### 3.3 Lock Payment Form

```php
Forms\Components\Section::make('Pembayaran')
    ->disabled(fn (?Penjualan $record) => $record && ! $record->canEditPayment())
    ->schema([...])
```

### 3.4 Tombol Action

```php
// POST: Draft → Final
Tables\Actions\Action::make('post')
    ->label('Posting')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->visible(fn (Penjualan $record) => $record->canPost())
    ->requiresConfirmation()
    ->modalHeading('Posting Penjualan')
    ->modalDescription('Setelah di-post, penjualan akan final dan tidak bisa diubah kecuali di-void 1x. Lanjutkan?')
    ->action(fn (Penjualan $record) => $record->post());

// VOID: Final → Draft (1x)
Tables\Actions\Action::make('void')
    ->label('Void ke Draft')
    ->icon('heroicon-o-arrow-uturn-left')
    ->color('warning')
    ->visible(fn (Penjualan $record) => $record->canVoid())
    ->requiresConfirmation()
    ->modalHeading('Void Penjualan ke Draft')
    ->modalDescription(function () {
        return "Anda hanya bisa melakukan ini 1 kali.\n\n"
            . "Setelah di-void:\n"
            . "• Status kembali ke Draft\n"
            . "• Item & Jasa tetap terkunci\n"
            . "• Hanya Pembayaran yang bisa diubah\n\n"
            . "Lanjutkan?";
    })
    ->action(fn (Penjualan $record) => $record->voidToDraft());

// LOCK: Final → Locked
Tables\Actions\Action::make('lock')
    ->label('Lock Final')
    ->icon('heroicon-o-lock-closed')
    ->color('danger')
    ->visible(fn (Penjualan $record) => $record->canLock())
    ->requiresConfirmation()
    ->modalHeading('Lock Penjualan')
    ->modalDescription('Penjualan akan terkunci permanen. Tidak ada yang bisa diubah lagi. Lanjutkan?')
    ->action(fn (Penjualan $record) => $record->lockFinal());

// DELETE (updated)
Tables\Actions\DeleteAction::make()
    ->visible(fn (Penjualan $record) => $record->canDelete())
    ->requiresConfirmation()
    ->modalHeading('Hapus Penjualan')
    ->modalDescription(function (Penjualan $record) {
        if (! $record->canDelete()) {
            return 'Penjualan tidak bisa dihapus karena terkait Tukar Tambah.';
        }
        $messages = ['Apakah Anda yakin ingin menghapus penjualan ini?'];
        if ($record->isFinal()) {
            $messages[] = '⚠️ Penjualan sudah Final. Stok akan dikembalikan ke batch.';
        }
        return implode("\n", $messages);
    });
```

### ✅ Testing Phase 3

```php
// File: tests/Feature/SalesModule/PenjualanResourceTest.php

test('draft list menampilkan hanya draft', function () {
    createPenjualan(['status_dokumen' => 'draft']);
    createPenjualan(['status_dokumen' => 'final']);
    
    get('/admin/penjualans?tab=draft')
        ->assertSee('PJ-Draft')
        ->assertDontSee('PJ-Final');
});

test('final list menampilkan hanya final', function () {
    createPenjualan(['status_dokumen' => 'draft']);
    createPenjualan(['status_dokumen' => 'final']);
    
    get('/admin/penjualans?tab=final')
        ->assertSee('PJ-Final')
        ->assertDontSee('PJ-Draft');
});

test('tombol post hanya muncul di draft', function () {
    $draft = createPenjualan(['status_dokumen' => 'draft']);
    $final = createPenjualan(['status_dokumen' => 'final']);
    
    // Assert via Livewire atau DOM
});
```

**Estimasi:** 3 hari

---

## ⚙️ PHASE 4: UPGRADE FEATURES (Business Rules)

**Goal:** Implementasi SN, Jasa filter, Payment status, Delete TT.

### 4.1 SN & Garansi Validasi

**File:** `app/Models/PenjualanItem.php`

```php
static::creating(function (PenjualanItem $item): void {
    // Validasi SN
    if (! empty($item->serials)) {
        $batch = StockBatch::where('pembelian_item_id', $item->id_pembelian_item)->first();
        $availableSNs = $batch?->serials ?? [];
        
        foreach ($item->serials as $sn) {
            if (! in_array($sn, $availableSNs)) {
                throw ValidationException::withMessages([
                    'serials' => "SN {$sn} tidak tersedia di batch ini."
                ]);
            }
        }
    }
    
    // ... existing logic
});
```

### 4.2 Jasa Filter Referensi Nota

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
public static function getJasaReferensiOptions(): array
{
    $alreadyOut = PenjualanJasa::whereNotNull('referensi_nota')
        ->pluck('referensi_nota')
        ->unique()
        ->toArray();
    
    return Penjualan::whereNotIn('no_nota', $alreadyOut)
        ->whereNotNull('no_nota')
        ->pluck('no_nota', 'id_penjualan')
        ->toArray();
}
```

**File:** `app/Models/PenjualanJasa.php`

```php
static::creating(function (PenjualanJasa $jasa): void {
    if ($jasa->referensi_nota) {
        $exists = PenjualanJasa::where('referensi_nota', $jasa->referensi_nota)
            ->where('id_penjualan', '!=', $jasa->id_penjualan)
            ->exists();
        
        if ($exists) {
            throw ValidationException::withMessages([
                'referensi_nota' => "Nota {$jasa->referensi_nota} sudah pernah di-out."
            ]);
        }
    }
});
```

### 4.3 Status Pembayaran TEMPO/LUNAS

**File:** `app/Models/Penjualan.php`

```php
public function getStatusPembayaranAttribute(): string
{
    $totalPaid = (float) $this->pembayaran()->sum('jumlah');
    $grandTotal = (float) $this->grand_total;
    
    return $totalPaid >= $grandTotal ? 'LUNAS' : 'TEMPO';
}
```

### 4.4 Delete TT Validation

Sudah diimplementasi di Phase 2.

### ✅ Testing Phase 4

```php
// File: tests/Unit/SalesModule/FeaturesTest.php

test('sn tidak valid gagal', function () {
    $batch = createStockBatch(5, ['serials' => ['SN001']]);
    $penjualan = createPenjualan();
    
    expect(fn () => createPenjualanItem($penjualan, $batch, 1, ['serials' => ['SN999']]))
        ->toThrow(ValidationException::class);
});

test('sn valid berhasil', function () {
    $batch = createStockBatch(5, ['serials' => ['SN001', 'SN002']]);
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batch, 2, ['serials' => ['SN001', 'SN002']]);
    
    expect($item->serials)->toBe(['SN001', 'SN002']);
});

test('jasa double referensi nota gagal', function () {
    createPenjualanJasa(['referensi_nota' => 'PJ-001']);
    
    expect(fn () => createPenjualanJasa(['referensi_nota' => 'PJ-001']))
        ->toThrow(ValidationException::class);
});

test('status tempo ketika belum lunas', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create(['jumlah' => 500000]);
    
    expect($penjualan->status_pembayaran)->toBe('TEMPO');
});

test('status lunas ketika sudah lunas', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create(['jumlah' => 1000000]);
    
    expect($penjualan->status_pembayaran)->toBe('LUNAS');
});
```

**Estimasi:** 2 hari

---

## 🧪 PHASE 5: INTEGRATION TESTING

**Goal:** End-to-end, race condition, cleanup.

### 5.1 End-to-End Flow Test

```php
// File: tests/Feature/SalesModule/EndToEndTest.php

test('alur lengkap draft → final → void → final → lock', function () {
    $batch = createStockBatch(10);
    
    // 1. Buat draft + item
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    
    expect($batch->fresh()->qty_available)->toBe(7);
    expect($penjualan->canEditItems())->toBeFalse(); // sudah ada item = locked
    
    // 2. Post → Final
    $penjualan->post();
    expect($penjualan->fresh()->isFinal())->toBeTrue();
    expect($penjualan->fresh()->canEditPayment())->toBeFalse();
    
    // 3. Void → Draft
    $penjualan->voidToDraft();
    expect($penjualan->fresh()->isDraft())->toBeTrue();
    expect($penjualan->fresh()->canEditPayment())->toBeTrue();
    expect($batch->fresh()->qty_available)->toBe(7); // stok tetap!
    
    // 4. Post lagi → Final
    $penjualan->post();
    expect($penjualan->fresh()->isFinal())->toBeTrue();
    expect($penjualan->fresh()->canVoid())->toBeFalse(); // sudah pernah void
    
    // 5. Lock
    $penjualan->lockFinal();
    expect($penjualan->fresh()->is_locked)->toBeTrue();
});
```

### 5.2 Race Condition Test

```php
test('concurrent penjualan dari batch yang sama tidak oversell', function () {
    $batch = createStockBatch(5);
    
    // Simulasi 2 transaksi bersamaan
    $penjualanA = createPenjualan();
    $penjualanB = createPenjualan();
    
    // Keduanya coba beli 3 unit dari stok 5
    createPenjualanItem($penjualanA, $batch, 3);
    
    expect(fn () => createPenjualanItem($penjualanB, $batch, 3))
        ->toThrow(\RuntimeException::class); // Stok tidak cukup
    
    expect($batch->fresh()->qty_available)->toBe(2);
});
```

### 5.3 Cleanup

- [ ] Hapus `qty_sisa` dependency dari `PenjualanItem` (jangan sentuh lagi)
- [ ] Pastikan `StockBatch` adalah single source of truth
- [ ] Update `MANUAL_TEST_INVENTORY.md` dengan skenario Penjualan

**Estimasi:** 1 hari

---

## 📅 Ringkasan Timeline

| Phase | Deskripsi | Estimasi | Testing |
|-------|-----------|----------|---------|
| **0** | Hotfix Sementara (optional) | 0.5 hari | 1 test |
| **1** | Fix Foundation (StockBatch) | 2 hari | 5+ tests |
| **2** | Upgrade Structure (Migration/Model) | 2 hari | 15+ tests |
| **3** | Upgrade UI (Filament) | 3 hari | 5+ tests |
| **4** | Upgrade Features (SN/Jasa/Payment) | 2 hari | 5+ tests |
| **5** | Integration & Cleanup | 1 hari | 3+ tests |
| **Total** | | **10.5 hari** | **34+ tests** |

---

## 🎯 Checklist Keseluruhan

### FIX (Foundation)
- [ ] F1: `PenjualanItem` pakai `StockBatch::decrementWithLock()`
- [ ] F2: Pessimistic locking via `decrementWithLock()`
- [ ] F3: `StockMutation` audit trail
- [ ] F4: Atomic `DB::transaction`
- [ ] F5: Validasi stok pakai `StockBatch.qty_available`
- [ ] F6: UI display pakai `StockBatch`
- [ ] F7: Hapus `max(0, ...)` silent bug
- [ ] F8: Observer sync ke StockBatch (hotfix)

### UPGRADE (Features)
- [ ] U1: Draft & Final state machine
- [ ] U2: Tab Draft / Final
- [ ] U3: Lock items/jasa setelah save
- [ ] U4: Void to Draft (1x)
- [ ] U5: Lock Final (permanent)
- [ ] U6: Hapus Final/Lock (stok kembali)
- [ ] U7: Status Pembayaran TEMPO/LUNAS
- [ ] U8: SN & Garansi validasi
- [ ] U9: Jasa filter nota already-out
- [ ] U10: Delete block kalau ada TT

---

*Roadmap ini akan di-update saat implementasi berjalan.*
