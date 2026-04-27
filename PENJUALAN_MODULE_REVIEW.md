# 🔍 Review Modul Penjualan — Analisis Bug, Celah & Rekomendasi Fix

> Dokumen ini adalah hasil review kode modul Penjualan (Sales) terhadap sistem inventory yang baru dibangun. Tujuan: mengidentifikasi inkonsistensi, celah, dan bug yang ada sebelum migrasi ke StockBatch.

---

## 📋 Ringkasan Eksekutif

| Aspek | Status | Risiko |
|-------|--------|--------|
| Sumber Stok | `PembelianItem.qty_sisa` (bukan StockBatch) | 🔴 **High** |
| Locking | Tidak ada | 🔴 **High** |
| Audit Trail | Tidak ada | 🟠 **Medium** |
| Sinkronisasi ke StockBatch | **Tidak ada** | 🔴 **Critical** |
| Race Condition | Rentan | 🔴 **High** |
| Atomic Transaction | Tidak ada | 🟠 **Medium** |
| Validasi RMA | Ada | 🟢 **OK** |

---

## 🏗️ Alur Bisnis Penjualan Saat Ini

```
┌─────────────────────────────────────────────────────────────────────┐
│  1. User pilih Produk di UI                                         │
│     └─> PenjualanResource::getBatchOptions()                        │
│         └─> Cari PembelianItem.qty_sisa > 0                         │
│                                                                     │
│  2. User pilih Batch (PembelianItem)                                │
│     └─> Simpan id_pembelian_item ke PenjualanItem                   │
│                                                                     │
│  3. Simpan PenjualanItem                                            │
│     └─> Event: creating()                                           │
│         └─> assertStockAvailable()                                  │
│             └─> Cek PembelianItem.qty_sisa (bukan StockBatch!)      │
│         └─> applyBatchDefaults()                                    │
│     └─> Event: created()                                            │
│         └─> applyStockMutation(batchId, -qty)                       │
│             └─> PembelianItem.qty_sisa -= qty                       │
│             └─> PembelianItem.save()                                │
│         └─> recalculatePenjualanTotals()                            │
│         └─> PembelianItemObserver.updated()                         │
│             └─> SyncStockToWooCommerce::dispatch()                  │
│                                                                     │
│  ⚠️ TIDAK ADA: update ke StockBatch.qty_available!                 │
│  ⚠️ TIDAK ADA: StockMutation audit trail!                          │
│  ⚠️ TIDAK ADA: pessimistic locking!                                │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🔴 BUG & CELAH KRITIS

### 1. **StockBatch Tidak Di-update Saat Penjualan (CRITICAL)**

**File:** `app/Models/PenjualanItem.php`

```php
protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
{
    // ...
    $qtyColumn = PembelianItem::qtySisaColumn();
    $batch = PembelianItem::query()->find($batchId);
    
    $updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
    $batch->{$qtyColumn} = $updatedQty;
    $batch->save();  // ❌ Hanya update qty_sisa!
}
```

**Masalah:**
- Penjualan mengurangi `PembelianItem.qty_sisa`
- Tapi **`StockBatch.qty_available` tidak berubah!**
- Hasil: **Dual source yang tidak sinkron**

**Contoh Skenario:**
```
Awal:
  PembelianItem.qty_sisa = 10
  StockBatch.qty_available = 10

Setelah penjualan 3 unit:
  PembelianItem.qty_sisa = 7     ← di-update
  StockBatch.qty_available = 10  ← ❌ TIDAK BERUBAH!

Hasil: Inventory menampilkan stok 10, padahal seharusnya 7
```

**Dampak:**
- Stok di InventoryResource (yang pakai StockBatch) **tidak akurat**
- Bisa terjadi **oversell** — jual barang yang sebenarnya sudah habis
- Data laporan tidak konsisten

---

### 2. **Tidak Ada Pessimistic Locking (Race Condition)**

**File:** `app/Models/PenjualanItem.php`

**Masalah:**
- `assertStockAvailable()` dan `applyStockMutation()` berjalan terpisah
- Tidak ada `DB::transaction()` + `lockForUpdate()`
- Dua user bisa jualan bareng dan melebihi stok

**Contoh Race Condition:**
```
User A dan User B sama-sama jualan 8 unit dari batch yang tersisa 10

Waktu 0ms:
  User A: assertStockAvailable(8) → OK (stok = 10)
  User B: assertStockAvailable(8) → OK (stok = 10)

Waktu 50ms:
  User A: applyStockMutation(-8) → qty_sisa = 2

Waktu 100ms:
  User B: applyStockMutation(-8) → qty_sisa = -6  ← ❌ NEGATIF!
```

**Bandingkan dengan StockOpname/Adjustment:**
```php
// ✅ Opname/Adjustment sudah pakai ini
StockBatch::incrementWithLock($batchId, $qty);  // Atomic + Lock
```

---

### 3. **Tidak Ada Audit Trail (StockMutation)**

**Masalah:**
- Setiap penjualan mengubah stok tapi **tidak tercatat** di `stock_mutations`
- Tidak bisa trace: siapa yang jual? kapan? berapa? batch mana?
- Sulit debugging kalau ada selisih stok

**Bandingkan dengan Opname/Adjustment:**
```php
// ✅ Opname/Adjustment sudah buat mutation
StockMutation::create([
    'type' => 'opname',
    'qty_change' => +5,
    'qty_before' => 10,
    'qty_after' => 15,
    'reference_type' => 'StockOpname',
    'reference_id' => $opnameId,
]);
```

---

### 4. **Logic Update PenjualanItem Berisiko**

**File:** `app/Models/PenjualanItem.php`

```php
static::updated(function (PenjualanItem $item): void {
    $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
    $originalQty = (int) $item->getOriginal('qty');

    if ($originalBatchId) {
        self::applyStockMutation($originalBatchId, $originalQty);  // Kembalikan stok lama
    }

    self::applyStockMutation($item->id_pembelian_item, -1 * (int) $item->qty);  // Kurangi stok baru
});
```

**Celah:**
1. **Tidak atomic** — kalau proses gagal di tengah, stok bisa corrupt
2. **Tidak ada rollback** — kalau `applyStockMutation` kedua gagal, stok pertama sudah dikembalikan
3. **Race condition** — dua user edit item yang sama = stok chaos

**Skenario Berbahaya:**
```
Item penjualan: batch A, qty 5
User edit jadi: batch B, qty 3

Langkah 1: Kembalikan 5 ke batch A → qty_sisa A +5
Langkah 2: Kurangi 3 dari batch B → qty_sisa B -3

❌ Kalau Langkah 2 gagal (DB error): 
   Batch A sudah +5 (kelebihan), Batch B tidak berubah
```

---

### 5. **Validasi Stok Menggunakan qty_sisa (Bukan StockBatch)**

**File:** `app/Models/PenjualanItem.php::assertStockAvailable()`

```php
$qtyColumn = PembelianItem::qtySisaColumn();
$availableQty = (int) ($batch->{$qtyColumn} ?? 0);  // ❌ Pakai qty_sisa
```

**Masalah:**
- Kalau `qty_sisa` dan `StockBatch.qty_available` tidak sinkron (lihat bug #1)
- Validasi bisa **loloskan penjualan melebihi stok aktual**

---

### 6. **PenjualanResource Menggunakan qty_sisa untuk Display**

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
public static function getBatchOptions(?int $productId, ?string $condition = null): array
{
    $qtyColumn = PembelianItem::qtySisaColumn();
    
    $items = PembelianItem::query()
        ->where($qtyColumn, '>', 0)  // ❌ Filter pakai qty_sisa
        ->whereDoesntHave('rmas', ...)
        ->get();
}

public static function getAvailableQty(int $productId, ...): int
{
    return (int) $query->sum($qtyColumn);  // ❌ Sum qty_sisa
}
```

**Masalah:**
- UI menampilkan stok dari `qty_sisa` yang mungkin sudah tidak akurat
- User bisa memilih batch yang sebenarnya stoknya sudah habis (di StockBatch)

---

### 7. **Tidak Ada Validasi Stok Negatif (max(0, ...) Menyembunyikan Bug)**

**File:** `app/Models/PenjualanItem.php::applyStockMutation()`

```php
$updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
```

**Masalah:**
- Sama dengan bug yang kita fix di Opname/Adjustment
- `max(0, ...)` menyembunyikan selisih stok
- Kalau stok jadi negatif, di-silent jadi 0
- Tidak ada error/alert ke user atau admin

---

### 8. **Observer PembelianItem Hanya Sync WooCommerce**

**File:** `app/Observers/PembelianItemObserver.php`

```php
public function updated(PembelianItem $pembelianItem): void
{
    if (! $pembelianItem->wasChanged('qty_sisa')) {
        return;
    }
    
    SyncStockToWooCommerce::dispatch($produk->id);  // ❌ Hanya WooCommerce
}
```

**Masalah:**
- Observer hanya mengirim sync ke WooCommerce
- **Tidak ada sync ke StockBatch.qty_available**
- Jadi meskipun ada observer, StockBatch tetap tidak ter-update

---

### 9. **TukarTambah Kemungkinan Memiliki Bug yang Sama**

Berdasarkan pencarian kode, `TukarTambahResource` juga menggunakan:
- `PembelianItem::qtySisaColumn()` untuk cek stok
- `applyStockMutation` pattern yang sama
- Tidak ada `StockBatch::decrementWithLock()`

**Status:** Belum diverifikasi 100%, tapi pola kode menunjukkan bug yang identik.

---

## 📊 Tabel Perbandingan: Opname/Adjustment vs Penjualan

| Fitur | Opname/Adjustment (Fixed ✅) | Penjualan (Current ❌) |
|-------|------------------------------|------------------------|
| **Sumber Stok** | StockBatch.qty_available | PembelianItem.qty_sisa |
| **Locking** | `lockForUpdate()` | Tidak ada |
| **Atomic** | `DB::transaction()` | Tidak ada |
| **Audit Trail** | StockMutation tercatat | Tidak ada |
| **Validasi Negatif** | Throw ValidationException | `max(0, ...)` silent |
| **Validasi RMA** | Ada | Ada ✅ |
| **Sync WooCommerce** | Bus::fake() | Observer dispatch |

---

## 🎯 Rekomendasi Fix (Blueprint Migrasi Penjualan)

### Phase A: Foundation Fix (Wajib — Sebelum production)

#### A1. Ganti `applyStockMutation` ke `StockBatch::decrementWithLock()`

**File:** `app/Models/PenjualanItem.php`

```php
// BEFORE (current)
protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
{
    $batch = PembelianItem::query()->find($batchId);
    $updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
    $batch->{$qtyColumn} = $updatedQty;
    $batch->save();
}

// AFTER (recommended)
protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
{
    $stockBatch = StockBatch::where('pembelian_item_id', $batchId)->first();
    
    if (! $stockBatch) {
        // Fallback: create StockBatch if not exists
        $pembelianItem = PembelianItem::find($batchId);
        if ($pembelianItem) {
            $stockBatch = StockBatch::create([
                'pembelian_item_id' => $batchId,
                'produk_id' => $pembelianItem->id_produk,
                'qty_total' => $pembelianItem->qty,
                'qty_available' => $pembelianItem->qty_sisa ?? $pembelianItem->qty,
            ]);
        }
    }
    
    if ($stockBatch && $qtyDelta < 0) {
        StockBatch::decrementWithLock(
            $stockBatch->id,
            abs($qtyDelta),
            [
                'type' => 'sale',
                'reference_type' => 'PenjualanItem',
                'reference_id' => $item->id_penjualan_item,
                'notes' => "Penjualan: {$item->qty} unit",
            ]
        );
    } elseif ($stockBatch && $qtyDelta > 0) {
        StockBatch::incrementWithLock(
            $stockBatch->id,
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

#### A2. Ganti Validasi Stok ke StockBatch

**File:** `app/Models/PenjualanItem.php::assertStockAvailable()`

```php
// BEFORE
$availableQty = (int) ($batch->{$qtyColumn} ?? 0);  // qty_sisa

// AFTER
$stockBatch = StockBatch::where('pembelian_item_id', $batchId)->first();
$availableQty = $stockBatch ? $stockBatch->qty_available : 0;
```

#### A3. Ganti UI Display ke StockBatch

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
// getBatchOptions() & getAvailableQty()
// Ganti dari PembelianItem.qty_sisa ke StockBatch.qty_available
```

### Phase B: Atomic Transaction & Locking

#### B1. Wrap PenjualanItem Save dalam Transaction

**File:** `app/Models/PenjualanItem.php`

```php
// Gunakan DB::transaction di event created/updated/deleted
// atau lebih baik: pindahkan logic ke Penjualan model saat posting
```

#### B2. Cegah Race Condition

**Opsi 1:** Lock di PenjualanItem level (kompleks)
**Opsi 2:** Lock di StockBatch level (sudah ada `decrementWithLock`)

**Rekomendasi:** Opsi 2 — karena `decrementWithLock` sudah handle race condition.

### Phase C: Hapus qty_sisa Dependency

#### C1. Hapus `applyStockMutation` yang update qty_sisa

Setelah semua modul pakai StockBatch:
- Hapus update ke `PembelianItem.qty_sisa`
- Biarkan `qty_sisa` jadi read-only legacy
- Sync periodik via command (sudah ada `inventory:sync-stock-batch`)

#### C2. Observer PembelianItem — tambah sync ke StockBatch

**File:** `app/Observers/PembelianItemObserver.php`

```php
// Tambah: kalau qty_sisa berubah, sync ke StockBatch
public function updated(PembelianItem $item): void
{
    if ($item->wasChanged('qty_sisa')) {
        $batch = $item->stockBatch;
        if ($batch) {
            $batch->update(['qty_available' => $item->qty_sisa]);
        }
        
        // WooCommerce sync tetap ada
        $this->dispatchSyncIfNeeded($item, 'updated');
    }
}
```

> ⚠️ **Catatan:** Ini adalah hotfix sementara. Tujuan akhir: Penjualan tidak sentuh qty_sisa sama sekali.

---

## 📋 Urutan Implementasi (Prioritas)

```
Week 1: Hotfix (Sementara, mengurangi risiko)
├── 1. Tambah observer sync qty_sisa → StockBatch
├── 2. Jalankan inventory:sync-stock-batch di production
└── 3. Monitor selisih stok

Week 2: Migrasi Penjualan ke StockBatch
├── 1. Ubah PenjualanItem::applyStockMutation() pakai decrementWithLock()
├── 2. Ubah assertStockAvailable() cek StockBatch
├── 3. Ubah PenjualanResource display pakai StockBatch
├── 4. Testing regression

Week 3: Migrasi TukarTambah ke StockBatch
├── 1. Sama seperti Penjualan
└── 2. Testing regression

Week 4: Cleanup
├── 1. Hapus dependency qty_sisa dari Penjualan
├── 2. Hapus dependency qty_sisa dari TukarTambah
├── 3. Verifikasi sync berjalan otomatis
└── 4. Rename/drop qty_sisa (opsional)
```

---

## 🆕 BLUEPRINT UPGRADE: BUSINESS RULES BARU (User Request)

### A. Draft & Final (Sama Seperti Pembelian)

#### Alur Baru Penjualan

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  STATUS PENJUALAN (REVISED)                                                 │
│                                                                             │
│  ┌──────────┐    Save       ┌──────────┐    Post Final    ┌──────────┐     │
│  │  DRAFT   │ ────────────> │  DRAFT   │ ───────────────> │  FINAL   │     │
│  │  (new)   │               │ (saved)  │                  │ (posted) │     │
│  └──────────┘               └──────────┘                  └──────────┘     │
│       │                          │                             │            │
│       │ ✅ Items editable        │ ❌ Items LOCKED             │            │
│       │ ✅ Jasa editable         │ ❌ Jasa LOCKED              │ Void (1x)  │
│       │ ✅ Payment editable      │ ✅ Payment editable         │            │
│       │ 🔒 Stok DEDUCT           │ 🔒 Stok DEDUCT              │ ▼          │
│       │                            │                             │            │
│       │                            │                             ▼            │
│       │                            │                      ┌──────────┐       │
│       │                            │                      │  DRAFT   │       │
│       │                            │                      │ (voided) │       │
│       │                            │                      └──────────┘       │
│       │                            │                            │            │
│       │                            │                            │            │
│       │                            │                            │ ❌ Items LOCKED    │
│       │                            │                            │ ❌ Jasa LOCKED     │
│       │                            │                            │ ✅ Payment editable│
│       │                            │                            │ 🔒 Stok DEDUCT     │
│       │                            │                            │ ❌ Tidak bisa void │
│       │                            │                            ▼            │
│       │                            │                      ┌──────────┐       │
│       │                            └────────────────────> │  FINAL   │       │
│       │                                                   │(re-post) │       │
│       │                                                   └──────────┘       │
│       │                                                         │           │
│       │                                                         │ Lock      │
│       │                                                         ▼           │
│       │                                                   ┌──────────┐      │
│       └──────────────────────────────────────────────────> │  LOCKED  │      │
│                                                            │ (final)  │      │
│                                                            └──────────┘      │
│                                                                  │           │
│                                                                  │           │
│                                                                  │ ❌ SEMUA LOCKED │
│                                                                  │ 🔒 Permanent    │
└─────────────────────────────────────────────────────────────────────────────┘
```

#### Rules (REVISED)

| Status | Edit Items | Edit Jasa | Edit Payment | Void to Draft | Hapus | Stok |
|--------|-----------|-----------|--------------|---------------|-------|------|
| **Draft (new)** | ✅ Bisa | ✅ Bisa | ✅ Bisa | — | ✅ Bisa | ✅ **Deduct** |
| **Draft (saved)** | ❌ **Lock** | ❌ **Lock** | ✅ Bisa | — | ✅ Bisa | ✅ **Deduct** |
| **Final (posted)** | ❌ Tidak | ❌ Tidak | ❌ Tidak | ✅ **1x** | ✅ **Bisa** | 🔒 **Lock** |
| **Draft (voided)** | ❌ **Lock** | ❌ **Lock** | ✅ Bisa | ❌ Sudah dipakai | ✅ Bisa | ✅ **Deduct** |
| **Final (locked)** | ❌ Tidak | ❌ Tidak | ❌ Tidak | ❌ Tidak | ✅ **Bisa** | 🔒 **Lock** |

**Catatan Penting:**
- **Draft (saved)**: Item & Jasa **LOCKED** begitu disimpan. Hanya pembayaran yang bisa diedit.
- **Final (posted)**: Semua field readonly. Bisa **Void → Draft 1 kali** untuk perbaikan pembayaran urgent. **Bisa dihapus** (stok dikembalikan).
- **Draft (voided)**: Status kembali ke Draft. Item & Jasa tetap locked. Hanya payment editable. **Tidak bisa void lagi.**
- **Final (locked)**: Status permanen. Tidak ada yang bisa diubah. **Tetap bisa dihapus** (stok dikembalikan).

#### UI Tab Header

```
┌─────────────────────────────────────────────┐
│  [ Tab: Draft ]  [ Tab: Final ]            │
│                                             │
│  Draft List:                                │
│  - PJ-202601-001  [Customer A]  Rp 5.000.000│
│  - PJ-202601-002  [Customer B]  Rp 3.200.000│
│                                             │
│  Final List:                                │
│  - PJ-202601-003  [Customer C]  Rp 8.500.000│
│  - PJ-202601-004  [Customer D]  Rp 1.200.000│
└─────────────────────────────────────────────┘
```

#### Implementasi

**File:** `app/Models/Penjualan.php`

```php
protected $fillable = [
    // ... existing fields
    'status_dokumen',   // 'draft' | 'final'
    'is_locked',        // true | false
    'posted_at',        // timestamp
    'posted_by_id',     // user id
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
    // Hanya draft yang BELUM punya item (baru dibuat)
    return $this->isDraft() 
        && ! $this->is_locked 
        && ! $this->items()->exists();
}

public function canEditJasa(): bool
{
    return $this->canEditItems(); // Sama rules
}

public function canEditPayment(): bool
{
    // Payment editable saat Draft (termasuk hasil void)
    return $this->isDraft() && ! $this->is_locked;
}

public function canVoid(): bool
{
    // Hanya Final yang belum pernah di-void dan belum lock
    return $this->isFinal() 
        && ! $this->is_locked 
        && ! $this->void_used;
}

public function post(): void
{
    if ($this->isFinal()) {
        throw new \Exception('Penjualan sudah final.');
    }
    
    DB::transaction(function () {
        StockMutation::where('reference_type', 'Penjualan')
            ->where('reference_id', $this->id)
            ->where('type', 'sale_draft')
            ->update(['type' => 'sale']);
        
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
        throw new \Exception('Penjualan tidak bisa di-void.');
    }
    
    $this->update([
        'status_dokumen' => 'draft',
        'void_used' => true,
        'voided_at' => now(),
        'voided_by_id' => auth()->id(),
    ]);
    
    // Stok TIDAK dikembalikan!
    // Item & Jasa tetap locked!
}

public function lockFinal(): void
{
    if ($this->is_locked) {
        throw new \Exception('Penjualan sudah terkunci.');
    }
    
    $this->update(['is_locked' => true]);
}
```

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
// Tab Filter
public static function getNavigationItems(): array
{
    return [
        NavigationItem::make('Draft')
            ->url(fn () => static::getUrl('index', ['tab' => 'draft']))
            ->icon('heroicon-o-pencil'),
        NavigationItem::make('Final')
            ->url(fn () => static::getUrl('index', ['tab' => 'final']))
            ->icon('heroicon-o-check-circle'),
    ];
}

// Query scope
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    $tab = request()->query('tab', 'draft');
    
    return $query->where('status_dokumen', $tab);
}

// Items: disable kalau sudah ada item (draft saved) atau final
Forms\Components\Repeater::make('items')
    ->disabled(fn (?Penjualan $record) => $record && ! $record->canEditItems())
    ->deletable(fn (?Penjualan $record) => $record && $record->canEditItems())
    ->addable(fn (?Penjualan $record) => $record && $record->canEditItems())

// Jasa: sama dengan items
Forms\Components\Repeater::make('jasaItems')
    ->disabled(fn (?Penjualan $record) => $record && ! $record->canEditJasa())
    ->deletable(fn (?Penjualan $record) => $record && $record->canEditJasa())
    ->addable(fn (?Penjualan $record) => $record && $record->canEditJasa())

// Tombol Post / Void / Lock
Tables\Actions\Action::make('post')
    ->label('Posting Final')
    ->visible(fn (Penjualan $record) => $record->isDraft())
    ->requiresConfirmation()
    ->modalHeading('Posting Penjualan')
    ->modalDescription('Setelah di-post, penjualan akan final dan tidak bisa diubah kecuali di-void 1x.')
    ->action(fn (Penjualan $record) => $record->post());

Tables\Actions\Action::make('void')
    ->label('Void ke Draft')
    ->color('warning')
    ->icon('heroicon-o-arrow-uturn-left')
    ->visible(fn (Penjualan $record) => $record->canVoid())
    ->requiresConfirmation()
    ->modalHeading('Void Penjualan ke Draft')
    ->modalDescription(function (Penjualan $record) {
        return "Anda hanya bisa melakukan ini 1 kali.\n\n"
            . "Setelah di-void:\n"
            . "• Status kembali ke Draft\n"
            . "• Item & Jasa tetap terkunci\n"
            . "• Hanya Pembayaran yang bisa diubah\n\n"
            . "Lanjutkan?";
    })
    ->action(fn (Penjualan $record) => $record->voidToDraft());

Tables\Actions\Action::make('lock')
    ->label('Lock Final')
    ->color('danger')
    ->visible(fn (Penjualan $record) => $record->isFinal() && ! $record->is_locked)
    ->requiresConfirmation()
    ->action(fn (Penjualan $record) => $record->lockFinal());
```

#### Stok Behavior (REVISED)

```
Ketika Draft baru dibuat:
  1. StockBatch.qty_available -= qty  (langsung deduct)
  2. StockMutation tercatat: type='sale_draft'
  
Ketika Draft disimpan (saved):
  → Item & Jasa LOCKED
  → Stok tetap terdeduct
  → Hanya payment yang bisa diedit
  
Ketika Draft dihapus:
  1. Kembalikan semua stok
  2. Hapus StockMutation draft
  
Ketika Draft di-post → Final:
  1. Ubah StockMutation type dari 'sale_draft' jadi 'sale'
  2. Lock stok permanen
  
Ketika Final di-void → Draft (1x):
  1. Status kembali ke 'draft'
  2. Stok tetap terdeduct (tidak dikembalikan!)
  3. Item & Jasa tetap locked
  4. Hanya payment bisa diedit
  5. Flag void_used = true
  
Ketika Draft (voided) di-post → Final (lagi):
  1. Status ke 'final'
  2. StockMutation type tetap 'sale'
  3. Tidak bisa void lagi (void_used sudah true)

Ketika Final dihapus (posted atau locked):
  1. Kembalikan semua stok ke batch
  2. Hapus StockMutation
  3. Hapus record Penjualan (soft delete atau hard delete tergantung kebijakan)
  4. **Validasi**: Tetap tidak bisa hapus kalau ada TT
```

---

### B. Status Pembayaran: Hanya TEMPO & LUNAS

#### Rules

| Status | Kondisi |
|--------|---------|
| **TEMPO** | `total_paid < grand_total` |
| **LUNAS** | `total_paid >= grand_total` |

#### Implementasi

**File:** `app/Models/Penjualan.php`

```php
public function getStatusAttribute(): string
{
    $totalPaid = (float) $this->pembayaran()->sum('jumlah');
    $grandTotal = (float) $this->grand_total;
    
    return $totalPaid >= $grandTotal ? 'LUNAS' : 'TEMPO';
}
```

**Validasi:**
- Hapus enum/status lain (kalau ada)
- Hanya 2 status yang valid

---

### C. Pertahankan SN & Garansi

#### Keputusan

- **PembelianItem**: Kolom `serials` dan `garansi` **TETAP ADA** (tidak dihapus)
- **PenjualanItem**: Bisa attach SN yang dijual ke field `serials` (json array)

#### Implementasi

```php
// Saat create PenjualanItem, user bisa pilih SN dari batch
// SN harus termasuk dalam batch yang dipilih

$availableSNs = $batch->serials ?? [];  // ['SN001', 'SN002', ...]

// Validasi: SN yang dipilih harus ada di batch
$selectedSNs = $itemData['serials'] ?? [];

foreach ($selectedSNs as $sn) {
    if (! in_array($sn, $availableSNs)) {
        throw ValidationException::withMessages([
            'serials' => "SN {$sn} tidak tersedia di batch ini."
        ]);
    }
}
```

#### UI

```
Form Penjualan Item:
├── Produk: [Intel Core i7]
├── Batch: [PO-001 | Qty: 5]
├── SN Tersedia: [SN001, SN002, SN003, SN004, SN005]
├── SN Dijual:    [SN001] [SN002] [+]  ← Multi-select/tag input
├── Qty: 2
├── Harga Jual: Rp 5.000.000
└── Garansi: 12 bulan (otomatis dari batch)
```

---

### D. Jasa — Sembunyikan Nota yang Sudah Di-Out

#### Business Rule

- Jasa bisa referensi ke nota Pembelian/Penjualan sebelumnya
- Kalau nota tersebut **sudah pernah di-out** di Penjualan lain → **sembunyikan/filter**
- Mencegah double-charge / duplicate service billing

#### Implementasi

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
public static function getJasaReferensiOptions(): array
{
    // Ambil semua nota yang sudah pernah di-out di penjualan
    $alreadyOutNotas = PenjualanJasa::query()
        ->whereNotNull('referensi_nota')
        ->pluck('referensi_nota')
        ->unique()
        ->toArray();
    
    // Filter: hanya tampilkan nota yang BELUM pernah di-out
    return Penjualan::query()
        ->whereNotIn('no_nota', $alreadyOutNotas)
        ->orWhereNull('no_nota')
        ->pluck('no_nota', 'id_penjualan')
        ->toArray();
}
```

**Validasi Double Out:**

```php
// Saat simpan PenjualanJasa
static::creating(function (PenjualanJasa $jasa): void {
    if ($jasa->referensi_nota) {
        $exists = PenjualanJasa::where('referensi_nota', $jasa->referensi_nota)
            ->where('id_penjualan', '!=', $jasa->id_penjualan)
            ->exists();
        
        if ($exists) {
            throw ValidationException::withMessages([
                'referensi_nota' => "Nota {$jasa->referensi_nota} sudah pernah di-out di penjualan lain."
            ]);
        }
    }
});
```

#### UI

```
Form Jasa:
├── Jasa: [Install OS]
├── Qty: 1
├── Harga: Rp 500.000
└── Referensi Nota: [Dropdown]
    ├── PJ-202601-001  ✅ (belum di-out)
    ├── PJ-202601-002  ✅ (belum di-out)
    ├── PJ-202601-003  ❌ (sudah di-out — disabled/gray)
    └── PJ-202601-004  ✅ (belum di-out)
```

---

### E. Validasi Delete: Tidak Bisa Hapus Kalau Ada TT

#### Business Rule

- Bisa hapus Penjualan **KECUALI** kalau punya `no_tt` (TukarTambah)
- Sama seperti validasi delete di Pembelian

#### Implementasi

**File:** `app/Models/Penjualan.php`

```php
public function canDelete(): bool
{
    // R12: Cegah hapus kalau ada TukarTambah
    if (filled($this->no_tt)) {
        return false;
    }
    
    // Final & Lock tetap BISA dihapus (stok dikembalikan)
    // Void → Draft juga BISA dihapus
    return true;
}

public function delete(): ?bool
{
    if (! $this->canDelete()) {
        throw ValidationException::withMessages([
            'delete' => 'Penjualan tidak bisa dihapus karena sudah terkait dengan Tukar Tambah (TT).'
        ]);
    }
    
    return parent::delete();
}
```

**File:** `app/Filament/Resources/PenjualanResource.php`

```php
Tables\Actions\DeleteAction::make()
    ->visible(fn (Penjualan $record) => $record->canDelete())
    ->requiresConfirmation()
    ->modalHeading('Hapus Penjualan')
    ->modalDescription(function (Penjualan $record) {
        if (! $record->canDelete()) {
            return 'Penjualan ini tidak bisa dihapus karena sudah terkait dengan Tukar Tambah.';
        }
        
        $messages = [
            'Apakah Anda yakin ingin menghapus penjualan ini?',
        ];
        
        if ($record->isFinal()) {
            $messages[] = '⚠️ Penjualan sudah Final. Stok akan dikembalikan ke batch.';
        }
        
        if ($record->isDraft() && $record->void_used) {
            $messages[] = '⚠️ Penjualan berasal dari Void. Stok akan dikembalikan.';
        }
        
        return implode("\n", $messages);
    }),
```

---

## 🏗️ Arsitektur Baru Modul Penjualan (Setelah Upgrade)

```
┌─────────────────────────────────────────────────────────────────────┐
│                    MODUL PENJUALAN (UPGRADED)                       │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  UI Layer                                                           │
│  ├── Tab Draft / Final                                              │
│  ├── Form Items (draft only)                                        │
│  ├── Form Pembayaran (draft & final pre-lock)                       │
│  └── Dropdown Batch (filter: not RMA, qty > 0)                     │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Business Logic Layer                                               │
│  ├── Draft: Deduct stok via StockBatch::decrementWithLock()        │
│  ├── Final: Lock stok (tidak bisa edit item)                        │
│  ├── Payment: TEMPO / LUNAS only                                    │
│  ├── SN Validation: Cek SN tersedia di batch                        │
│  ├── Jasa: Filter referensi nota (hide already-out)                 │
│  └── Delete: Block kalau ada TT                                     │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Data Layer                                                         │
│  ├── Penjualan: status_dokumen, is_locked, posted_at               │
│  ├── PenjualanItem: id_pembelian_item (batch), serials[]           │
│  ├── StockBatch: qty_available (single source of truth)            │
│  ├── StockMutation: audit trail (type='sale'/'sale_draft')         │
│  └── PenjualanPembayaran: jumlah, metode, tanggal                  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📋 Checklist Implementasi Upgrade

### Phase 1: Struktur Data (1 hari)
- [ ] Migration: tambah `status_dokumen`, `is_locked`, `void_used`, `posted_at`, `posted_by_id`, `voided_at`, `voided_by_id` ke `tb_penjualan`
- [ ] Backfill: set existing data jadi `status_dokumen = 'final'`, `void_used = false`
- [ ] Tambah field `no_tt` ke `tb_penjualan` (kalau belum ada)
- [ ] Tambah field `referensi_nota` ke `tb_penjualan_jasa`
- [ ] Pertahankan kolom `serials` di `tb_pembelian_item`

### Phase 2: Model & Service (2 hari)
- [ ] Update `Penjualan` model: methods `isDraft()`, `isFinal()`, `post()`, `lockFinal()`, `canDelete()`
- [ ] Update `PenjualanItem` model: pakai `StockBatch::decrementWithLock()`
- [ ] Update `PenjualanItem` model: validasi SN tersedia
- [ ] Update `PenjualanJasa` model: validasi double referensi nota
- [ ] Buat `PenjualanService` untuk atomic transaction

### Phase 3: Filament Resource (3 hari)
- [ ] Tab header Draft / Final
- [ ] Item & Jasa repeater: editable hanya saat Draft BARU (belum ada item)
- [ ] Item & Jasa repeater: locked setelah disimpan (Draft saved/Final/Voided)
- [ ] Payment form: editable saat Draft (termasuk hasil void)
- [ ] Tombol Post (Draft → Final)
- [ ] Tombol Void ke Draft (Final → Draft, 1x, dengan konfirmasi modal)
- [ ] Tombol Lock Final (Final → Locked, permanent)
- [ ] Hide delete kalau ada TT
- [ ] Dropdown referensi nota: filter already-out
- [ ] SN selector multi-select

### Phase 4: Stock Integration (1 hari)
- [ ] Draft baru: deduct stok via `StockBatch::decrementWithLock()`
- [ ] Draft baru: catat `StockMutation` type='sale_draft'
- [ ] Draft saved: stok tetap terdeduct, item locked
- [ ] Draft delete: return all stok, hapus `StockMutation`
- [ ] Final post: ubah `StockMutation` type='sale_draft' → 'sale'
- [ ] Final post: lock stok (tidak bisa dikembalikan)
- [ ] Final void → Draft: status kembali ke Draft, stok TETAP terdeduct
- [ ] Final void → Draft: flag `void_used = true`, tidak bisa void lagi
- [ ] Audit trail: `StockMutation` setiap perubahan

### Phase 5: Testing (2 hari)
- [ ] Test: Draft baru deduct stok
- [ ] Test: Draft saved lock item & jasa
- [ ] Test: Draft delete return stok
- [ ] Test: Final post ubah mutation type
- [ ] Test: Final void ke Draft (1x)
- [ ] Test: Draft hasil void hanya bisa edit payment
- [ ] Test: Stok tidak dikembalikan saat void
- [ ] Test: Lock final mencegah semua edit & void
- [ ] Test: Payment TEMPO/LUNAS
- [ ] Test: SN validation
- [ ] Test: Jasa referensi nota filter
- [ ] Test: Delete block kalau ada TT
- [ ] Test: Race condition concurrent

---

## 🧪 Test Case Lengkap (Upgrade + Fix)

```php
// === DRAFT & FINAL ===

test('draft penjualan mengurangi stok batch', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    
    expect($batch->fresh()->qty_available)->toBe(7);
    expect(StockMutation::where('type', 'sale_draft')->count())->toBe(1);
});

test('draft bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    
    $penjualan->delete();
    
    expect($batch->fresh()->qty_available)->toBe(10);
    expect(StockMutation::where('type', 'sale_draft')->count())->toBe(0);
});

test('draft baru bisa edit item dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']); // baru dibuat, belum save item
    
    expect($penjualan->canEditItems())->toBeTrue();
    expect($penjualan->canEditJasa())->toBeTrue();
    expect($penjualan->canEditPayment())->toBeTrue();
});

test('draft saved tidak bisa edit item dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 2); // sudah ada item
    $penjualan->refresh();
    
    expect($penjualan->canEditItems())->toBeFalse();   // LOCKED
    expect($penjualan->canEditJasa())->toBeFalse();     // LOCKED
    expect($penjualan->canEditPayment())->toBeTrue();   // tetap bisa
});

test('final tidak bisa edit item, jasa, maupun payment', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => false]);
    
    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeFalse();
    expect($penjualan->canVoid())->toBeTrue(); // tapi bisa void!
});

test('final bisa di-void ke draft 1x', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    
    expect($penjualan->canVoid())->toBeTrue();
    
    $penjualan->voidToDraft();
    
    expect($penjualan->fresh()->isDraft())->toBeTrue();
    expect($penjualan->fresh()->void_used)->toBeTrue();
    expect($penjualan->fresh()->canVoid())->toBeFalse(); // tidak bisa void lagi!
});

test('draft hasil void hanya bisa edit payment', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    createPenjualanItem($penjualan, $batch, 2);
    $penjualan->voidToDraft();
    $penjualan->refresh();
    
    expect($penjualan->canEditItems())->toBeFalse();  // tetap locked
    expect($penjualan->canEditJasa())->toBeFalse();    // tetap locked
    expect($penjualan->canEditPayment())->toBeTrue();  // hanya ini
});

test('stok tidak dikembalikan saat void', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post(); // final
    
    expect($batch->fresh()->qty_available)->toBe(7); // stok terdeduct
    
    $penjualan->voidToDraft(); // void
    
    expect($batch->fresh()->qty_available)->toBe(7); // stok TETAP 7!
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
    $penjualan->post(); // jadi final
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->delete(); // hapus final
    
    expect($batch->fresh()->qty_available)->toBe(10); // stok kembali!
    expect(StockMutation::where('reference_id', $penjualan->id)->count())->toBe(0);
});

test('final locked bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => true]);
    createPenjualanItem($penjualan, $batch, 3);
    
    expect($batch->fresh()->qty_available)->toBe(7);
    
    $penjualan->delete(); // hapus locked
    
    expect($batch->fresh()->qty_available)->toBe(10); // stok kembali!
});

test('draft hasil void bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post(); // final
    $penjualan->voidToDraft(); // void → draft
    
    expect($batch->fresh()->qty_available)->toBe(7); // stok tetap terdeduct
    
    $penjualan->delete(); // hapus draft hasil void
    
    expect($batch->fresh()->qty_available)->toBe(10); // stok kembali!
});

// === STATUS PEMBAYARAN ===

test('status tempo ketika belum lunas', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create(['jumlah' => 500000]);
    
    expect($penjualan->status)->toBe('TEMPO');
});

test('status lunas ketika pembayaran >= grand_total', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create(['jumlah' => 1000000]);
    
    expect($penjualan->status)->toBe('LUNAS');
});

// === SN & GARANSI ===

test('penjualan dengan sn yang valid berhasil', function () {
    $batch = createStockBatch(5, ['serials' => ['SN001', 'SN002', 'SN003']]);
    
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batch, 2, ['serials' => ['SN001', 'SN002']]);
    
    expect($item->serials)->toBe(['SN001', 'SN002']);
});

test('penjualan dengan sn tidak valid gagal', function () {
    $batch = createStockBatch(5, ['serials' => ['SN001']]);
    
    expect(fn() => createPenjualanItem($penjualan, $batch, 1, ['serials' => ['SN999']]))
        ->toThrow(ValidationException::class);
});

// === JASA REFERENSI NOTA ===

test('referensi nota yang sudah di-out tidak muncul di dropdown', function () {
    $notaA = createPenjualan(['no_nota' => 'PJ-001']);
    $notaB = createPenjualan(['no_nota' => 'PJ-002']);
    
    // Nota A sudah di-out
    createPenjualanJasa(['referensi_nota' => 'PJ-001']);
    
    $options = PenjualanResource::getJasaReferensiOptions();
    
    expect($options)->toHaveKey($notaB->id)
        ->not->toHaveKey($notaA->id);
});

test('jasa dengan referensi nota double out gagal', function () {
    createPenjualanJasa(['referensi_nota' => 'PJ-001']);
    
    expect(fn() => createPenjualanJasa(['referensi_nota' => 'PJ-001']))
        ->toThrow(ValidationException::class);
});

// === VALIDASI DELETE ===

test('bisa hapus penjualan tanpa tt', function () {
    $penjualan = createPenjualan(['no_tt' => null]);
    
    expect($penjualan->canDelete())->toBeTrue();
});

test('tidak bisa hapus penjualan dengan tt', function () {
    $penjualan = createPenjualan(['no_tt' => 'TT-001']);
    
    expect($penjualan->canDelete())->toBeFalse();
});

// === RACE CONDITION ===

test('concurrent draft penjualan tidak oversell', function () {
    $batch = createStockBatch(5);
    
    // Simulasi 2 draft penjualan bersamaan
    // Salah satu harus gagal
});
```

---

## 📌 Ringkasan Upgrade (User Request)

| # | Fitur | Status Sekarang | Setelah Upgrade | File |
|---|-------|----------------|-----------------|------|
| 1 | Draft/Final | ❌ Tidak ada | ✅ Draft/Final + Lock + Void 1x | `Penjualan` model |
| 2 | Tab Header | ❌ Single list | ✅ Tab Draft + Final | `PenjualanResource` |
| 3 | Stok Draft | ❌ Langsung final | ✅ Deduct saat draft baru | `PenjualanItem` |
| 4 | Edit Items (Draft) | ❌ Bisa edit kapan saja | ✅ Lock setelah save | `PenjualanResource` |
| 5 | Edit Jasa (Draft) | ❌ Bisa edit kapan saja | ✅ Lock setelah save | `PenjualanResource` |
| 6 | Edit Payment | ❌ Biasa | ✅ Draft & Voided only | `PenjualanResource` |
| 7 | Void to Draft | ❌ Tidak ada | ✅ 1x dari Final (konfirmasi) | `Penjualan` model |
| 8 | Hapus Final | ❌ Bebas | ✅ Bisa hapus (stok kembali) | `Penjualan` model |
| 9 | Hapus Locked | ❌ Bebas | ✅ Bisa hapus (stok kembali) | `Penjualan` model |
| 10 | Status Bayar | ❌ Bisa banyak | ✅ TEMPO/LUNAS | `Penjualan` model |
| 11 | SN & Garansi | ✅ Ada | ✅ Pertahankan | `PembelianItem` |
| 12 | Jasa Ref Nota | ❌ Tidak ada filter | ✅ Filter already-out | `PenjualanJasa` |
| 13 | Delete Validasi TT | ❌ Bebas | ✅ Block kalau ada TT | `Penjualan` model |

---

*Dokumen ini diperbarui dengan business rules baru sesuai request user.*


---

## 💡 Saran Arsitektur Jangka Panjang

### Opsi 1: Service Layer (Recommended)

```php
class PenjualanService
{
    public function createItem(Penjualan $penjualan, array $data): PenjualanItem
    {
        return DB::transaction(function () use ($penjualan, $data) {
            $item = $penjualan->items()->create($data);
            
            // Atomic: stok dikurangi dalam transaction yang sama
            StockBatch::decrementWithLock(
                $item->stockBatch->id,
                $item->qty,
                ['type' => 'sale', ...]
            );
            
            return $item;
        });
    }
}
```

### Opsi 2: Event-Driven (Lebih Kompleks)

Gunakan event `PenjualanItemCreated` → listener `DeductStock` → dispatch job.

**Tapi risiko:** Event listener berjalan di luar transaction utama = race condition.

### Rekomendasi: **Opsi 1** (Service Layer)

---

## 📌 Kesimpulan

| # | Isu | Tingkat | Action |
|---|-----|---------|--------|
| 1 | StockBatch tidak di-update | 🔴 Critical | Fix segera (hotfix observer) |
| 2 | Tidak ada locking | 🔴 High | Migrasi ke decrementWithLock() |
| 3 | Tidak ada audit trail | 🟠 Medium | Tambah StockMutation |
| 4 | Update logic tidak atomic | 🔴 High | Wrap dalam transaction |
| 5 | Validasi pakai qty_sisa | 🔴 High | Ganti ke StockBatch |
| 6 | UI display qty_sisa | 🟠 Medium | Ganti ke StockBatch |
| 7 | max(0, ...) silent bug | 🟠 Medium | Throw exception |
| 8 | TukarTambah kemungkinan sama | 🟠 Medium | Verifikasi & fix |

**Status keseluruhan modul Penjualan:**
- ✅ Fitur bisnis jalan
- ❌ Data integrity tidak terjamin
- ❌ Tidak ada audit trail
- ❌ Rentan race condition
- ❌ StockBatch tidak sinkron

**Prioritas tertinggi:**
1. **Hotfix observer** (1 hari) — sync qty_sisa → StockBatch sementara
2. **Migrasi ke StockBatch** (1 minggu) — Penjualan pakai decrementWithLock()

---

*Dokumen ini dibuat untuk persiapan blueprint migrasi Penjualan ke StockBatch.*
