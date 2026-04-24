# Rencana Perbaikan Modul Inventory

> **Versi:** 1.0 | April 2026  
> **Tujuan:** Rencana bertahap untuk memperbaiki semua bug dan celah yang teridentifikasi

---

## Strategi: Phased Approach

Perbaikan dibagi menjadi **4 fase** berdasarkan:
1. **Risiko** — bug yang paling berbahaya diperbaiki lebih dulu
2. **Ketergantungan** — beberapa bug harus diperbaiki bersamaan
3. **Downtime** — perbaikan yang tidak mengganggu operasional diutamakan

```
Fase 1: Quick Wins (1-2 hari)
  └─ BUG-03, BUG-04, BUG-06
     └─ Low risk, high impact, independen

Fase 2: Core Fixes (3-5 hari)
  └─ BUG-01, BUG-02, BUG-09
     └─ Memerlukan keputusan arsitektur, saling bergantung

Fase 3: Policy & Performance (2-3 hari)
  └─ BUG-05, BUG-07, BUG-08
     └─ Cleanup, optimasi, sinkronisasi policy

Fase 4: Feature Gaps (3-4 hari)
  └─ BUG-10, BUG-11, BUG-12, BUG-13
     └─ Fitur yang belum terimplementasi
```

---

## Fase 1: Quick Wins (Estimasi: 1-2 hari)

### 1.1 BUG-03: Stock Opname/Adjustment Posting Non-Atomic

**File:** `app/Models/StockOpname.php`, `app/Models/StockAdjustment.php`

**Masalah:** `post()` loop tanpa DB transaction.

**Perbaikan:**

```php
// StockOpname::post()
public function post(User $user = null): void
{
    if ($this->isPosted()) {
        return;
    }

    DB::transaction(function () {
        foreach ($this->items as $item) {
            if ($item->selisih === 0) {
                continue;
            }
            $item->applyToBatch();
        }

        $this->forceFill([
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by_id' => $user?->getKey(),
        ])->save();
    });
}
```

**Risiko:** Rendah. Hanya menambahkan wrapper transaction.

**Test:** Buat stock opname dengan 3 item, mock error di item ke-2, verifikasi semua item tidak ter-apply.

---

### 1.2 BUG-04: Stock Adjustment Bisa Membuat Stok Negatif

**File:** `app/Models/StockAdjustmentItem.php`

**Masalah:** `applyToBatch()` menggunakan `max(0, ...)` yang menyembunyikan selisih.

**Perbaikan:**

```php
public function applyToBatch(): void
{
    $batch = $this->pembelianItem;
    if (! $batch) {
        return;
    }

    $qtyColumn = PembelianItem::qtySisaColumn();
    $currentQty = (int) ($batch->{$qtyColumn} ?? 0);
    $newQty = $currentQty + (int) $this->qty;

    if ($newQty < 0) {
        throw new \Exception(
            "Penyesuaian tidak valid. Stok saat ini: {$currentQty}, "
            . "Penyesuaian: {$this->qty}. Hasil akan negatif: {$newQty}."
        );
    }

    $batch->{$qtyColumn} = $newQty;
    $batch->save();
}
```

**Risiko:** Rendah. Menambahkan validasi sebelum write.

**Test:** Buat adjustment dengan qty negatif lebih besar dari stok, verifikasi exception.

---

### 1.3 BUG-06: Dead Code Duplicate Creating Event

**File:** `app/Models/PembelianItem.php`

**Masalah:** Callback `creating` kedua (line 97-105) tidak melakukan apa-apa.

**Perbaikan:** Hapus blok ini:

```php
// HAPUS BARIS 97-105:
static::creating(function (PembelianItem $item) {
    try {
        // Validasi sudah dilakukan di atas
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
        throw ValidationException::withMessages([
            'items' => 'GAGAL SIMPAN: Produk dengan kondisi yang sama tidak boleh duplikat.'
        ]);
    }
});
```

**Risiko:** Sangat rendah. Code removal, tidak mengubah behavior.

---

## Fase 2: Core Fixes (Estimasi: 3-5 hari)

### 2.1 Keputusan Arsitektur: Dual Stock Tracking (BUG-01)

**Sebelum memulai Fase 2, keputusan ini harus diambil:**

| Opsi | Deskripsi | Pro | Kontra |
|------|-----------|-----|--------|
| **A: StockBatch sebagai SoT** | Semua operasi stok melalui StockBatch | Lebih clean, locking built-in | Migrasi besar, perlu update semua flow |
| **B: qty_sisa sebagai SoT** | Hapus StockBatch, pertahankan qty_sisa | Perubahan minimal, tidak ada migrasi | Kehilangan fitur locking yang sudah ada di StockBatch |

**Rekomendasi: Opsi A** — StockBatch sebagai single source of truth. Alasan:
- StockBatch sudah punya `decrementWithLock()` dan `incrementWithLock` pattern
- Lebih mudah audit dan tracking per batch
- Mendukung R17 (pessimistic locking) secara native

---

### 2.2 BUG-01: Migrasi ke StockBatch sebagai Single Source of Truth

**File yang terpengaruh:**
- `app/Models/StockBatch.php` — tambah method `incrementWithLock()`
- `app/Models/PembelianItem.php` — sync StockBatch saat qty berubah
- `app/Models/PenjualanItem.php` — ganti `applyStockMutation` → `StockBatch::decrementWithLock()`
- `app/Models/StockOpnameItem.php` — ganti `applyToBatch` → StockBatch operation
- `app/Models/StockAdjustmentItem.php` — ganti `applyToBatch` → StockBatch operation
- `app/Filament/Resources/InventoryResource.php` — query dari StockBatch, bukan qty_sisa
- `app/Services/POS/CheckoutPosAction.php` — query batch dari StockBatch

**Langkah:**

1. **Tambah `incrementWithLock()` di StockBatch:**
```php
public static function incrementWithLock(int $batchId, int $qty, string $reason = ''): bool
{
    return DB::transaction(function () use ($batchId, $qty) {
        $batch = self::lockForUpdate()->find($batchId);
        if (! $batch) {
            throw new \Exception('Batch tidak ditemukan');
        }
        $batch->increment('qty_available', $qty);
        $batch->update(['locked_at' => now()]);
        return true;
    }, 5);
}
```

2. **Sync StockBatch saat PembelianItem dibuat/diupdate:**
```php
static::created(function (PembelianItem $item): void {
    if ($item->qty > 0) {
        StockBatch::create([...]);
    }
});

static::updated(function (PembelianItem $item): void {
    if ($item->isDirty('qty') || $item->isDirty('qty_masuk')) {
        $stockBatch = $item->stockBatch;
        if ($stockBatch) {
            $stockBatch->update([
                'qty_total' => $item->qty,
                'qty_available' => $item->qty_sisa,
            ]);
        }
    }
});

static::deleted(function (PembelianItem $item): void {
    $item->stockBatch?->delete();
});
```

3. **Ganti semua `applyStockMutation()` → `StockBatch::decrementWithLock()`:**
```php
// PenjualanItem::created
static::created(function (PenjualanItem $item): void {
    if ($item->id_pembelian_item) {
        StockBatch::decrementWithLock($item->id_pembelian_item, (int) $item->qty);
    }
    self::recalculatePenjualanTotals($item);
});
```

4. **Ganti query di InventoryResource:**
```php
// Sebelum:
->whereHas('pembelianItems', fn($q) => $q->where($qtySisaColumn, '>', 0))

// Sesudah:
->whereHas('pembelianItems.stockBatch', fn($q) => $q->where('qty_available', '>', 0))
```

5. **Data migration script** — backfill StockBatch.qty_available dari PembelianItem.qty_sisa:
```php
DB::table('stock_batches')
    ->join('tb_pembelian_item', 'stock_batches.pembelian_item_id', '=', 'tb_pembelian_item.id_pembelian_item')
    ->update([
        'stock_batches.qty_available' => DB::raw('tb_pembelian_item.qty_sisa'),
    ]);
```

**Risiko:** Tinggi. Mengubah banyak file. Perlu testing menyeluruh.

**Test:**
- Test suite untuk setiap flow: pembelian, penjualan, POS, opname, adjustment
- Load test concurrent sales untuk verifikasi locking
- Verifikasi inventory report akurat

---

### 2.3 BUG-02: Race Condition pada Stok

**File:** `app/Models/PenjualanItem.php`, `app/Models/StockBatch.php`

**Masalah:** `applyStockMutation()` tanpa locking.

**Perbaikan:** Sudah ter-cover oleh BUG-01. Setelah migrasi ke StockBatch, semua operasi stok menggunakan `StockBatch::decrementWithLock()` yang sudah punya `lockForUpdate()` dan DB transaction.

**Jika Opsi B dipilih (pertahankan qty_sisa):**
```php
protected static function applyStockMutation(?int $batchId, int $qtyDelta): void
{
    if (! $batchId || $qtyDelta === 0) {
        return;
    }

    DB::transaction(function () use ($batchId, $qtyDelta) {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $batch = PembelianItem::query()->lockForUpdate()->find($batchId);
        if (! $batch) {
            return;
        }
        $updatedQty = max(0, (int) ($batch->{$qtyColumn} ?? 0) + $qtyDelta);
        $batch->{$qtyColumn} = $updatedQty;
        $batch->save();
    });
}
```

---

### 2.4 BUG-09: PenjualanItem Update — Double Mutation Tanpa Transaction

**File:** `app/Models/PenjualanItem.php`

**Masalah:** Restore batch lama + deduct batch baru tanpa transaction.

**Perbaikan:**

```php
static::updated(function (PenjualanItem $item): void {
    $originalBatchId = (int) $item->getOriginal('id_pembelian_item');
    $originalQty = (int) $item->getOriginal('qty');
    $newBatchId = (int) $item->id_pembelian_item;
    $newQty = (int) $item->qty;

    DB::transaction(function () use ($originalBatchId, $originalQty, $newBatchId, $newQty) {
        // Restore stok batch lama
        if ($originalBatchId) {
            StockBatch::incrementWithLock($originalBatchId, $originalQty, 'update_restore');
        }

        // Kurangi stok batch baru
        if ($newBatchId) {
            StockBatch::decrementWithLock($newBatchId, $newQty);
        }

        self::recalculatePenjualanTotals($item);
    });
});
```

**Risiko:** Sedang. Mengubah behavior update.

**Test:** Update PenjualanItem dari batch A ke batch B, verifikasi stok A naik dan B turun.

---

## Fase 3: Policy & Performance (Estimasi: 2-3 hari)

### 3.1 BUG-05: Kontradiksi Policy R01 vs FIFO

**File:** `docs/module/PURCHASE_MODULE_POLICY_1.md`

**Keputusan yang perlu diambil:**

- Jika FIFO memang diinginkan → update policy R01 menjadi: "Sistem menggunakan FIFO untuk alokasi batch saat penjualan"
- Jika FIFO tidak diinginkan → ubah logika alokasi di `CheckoutPosAction`, `CreatePenjualan`, `ItemsRelationManager`

**Rekomendasi:** Update policy. FIFO adalah behavior yang benar dan sudah diimplementasi dengan baik.

---

### 3.2 BUG-07: Cache Flush Terlalu Agresif

**File:** `app/Support/CacheHelper.php`, `app/Models/Pembelian.php`, `app/Models/Penjualan.php`

**Masalah:** `CacheHelper::flush([CacheHelper::TAG_PEMBELIAN])` menghapus semua cache pembelian.

**Perbaikan:**

```php
// Tambah method di CacheHelper:
public static function flushKey(string $type, int $id): void
{
    $key = self::key('calc', $type, ['id' => $id]);
    self::getCacheStore()->forget($key);
}

// Di Pembelian::clearCalculationCache():
public function clearCalculationCache(): void
{
    CacheHelper::flushKey(CacheHelper::TAG_PEMBELIAN, $this->id_pembelian);
}
```

**Risiko:** Rendah. Perubahan scope dari tag-level ke key-level.

---

### 3.3 BUG-08: TukarTambah Static Flag Thread-Safety

**File:** `app/Models/TukarTambah.php`, `app/Models/Pembelian.php`, `app/Models/Penjualan.php`

**Perbaikan:** Ganti static flag dengan context-aware approach:

```php
// Di TukarTambah::deleting():
static::deleting(function (TukarTambah $tt): void {
    DB::transaction(function () use ($tt) {
        // Gunakan context key di request/session
        request()->merge(['tukar_tambah_cascade_delete' => true]);

        try {
            $tt->penjualan?->delete();
            $tt->pembelian?->delete();
        } finally {
            request()->offsetUnset('tukar_tambah_cascade_delete');
        }
    });
});

// Di Pembelian::deleting():
static::deleting(function (Pembelian $pembelian): void {
    if (request()->boolean('tukar_tambah_cascade_delete')) {
        // Skip validation, allow delete
        return;
    }
    // ... existing validation
});
```

**Alternatif:** Gunakan method parameter alih-alih global state:
```php
$pembelian->delete(skipTukarTambahCheck: true);
```

**Risiko:** Sedang. Mengubah cara cascade deletion bekerja.

---

## Fase 4: Feature Gaps (Estimasi: 3-4 hari)

### 4.1 BUG-10: StockBatch Tidak Sync saat Update PembelianItem

**File:** `app/Models/PembelianItem.php`

**Perbaikan:** Sudah ter-cover oleh BUG-01 (Fase 2). Saat migrasi ke StockBatch, tambahkan sync di `updated` event.

---

### 4.2 BUG-11: RMA Selesai Tidak Mengembalikan Stok

**File:** `app/Models/Rma.php`

**Perbaikan:**

```php
static::updated(function (Rma $rma): void {
    $originalStatus = $rma->getOriginal('status_garansi');
    $newStatus = $rma->status_garansi;

    // Jika RMA selesai dan sebelumnya aktif, kembalikan stok
    if (
        $newStatus === self::STATUS_SELESAI
        && in_array($originalStatus, self::activeStatuses(), true)
    ) {
        $batch = $rma->pembelianItem;
        if ($batch) {
            DB::transaction(function () use ($batch, $rma) {
                // Buat Stock Adjustment otomatis
                $adjustment = StockAdjustment::create([
                    'status' => 'posted',
                    'tanggal' => now(),
                    'sumber' => 'rma',
                    'sumber_id' => $rma->getKey(),
                    'catatan' => "Pengembalian stok RMA {$rma->no_nota}",
                ]);

                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'produk_id' => $batch->id_produk,
                    'pembelian_item_id' => $batch->id_pembelian_item,
                    'qty' => 1, // atau qty yang diklaim
                    'keterangan' => "RMA selesai, stok dikembalikan",
                ]);
            });
        }
    }
});
```

**Catatan:** Perlu kolom `qty` di RMA jika 1 RMA bisa untuk lebih dari 1 unit. Saat ini RMA tidak punya kolom qty.

**Risiko:** Sedang. Menambah fitur baru.

---

### 4.3 BUG-12: Inventory Resource Tidak Filter Gudang

**File:** `app/Filament/Resources/InventoryResource.php`

**Perbaikan:**

```php
// Tambah filter gudang di table:
->filters([
    SelectFilter::make('gudang_id')
        ->label('Gudang')
        ->relationship('gudang', 'nama_gudang')
        ->searchable()
        ->preload(),
    // ... existing filters
])

// Tambah scope:
protected static function applyInventoryScopes(Builder $query): Builder
{
    // ... existing code
    if (request()->filled('gudang_id')) {
        $query->whereHas('pembelianItems', function ($q) {
            $q->whereHas('pembelian', function ($q2) {
                $q2->where('gudang_id', request('gudang_id'));
            });
        });
    }
}
```

**Catatan:** Memerlukan kolom `gudang_id` di `tb_pembelian` (saat ini belum ada).

**Risiko:** Rendah. Menambah filter, tidak mengubah logic existing.

---

### 4.4 BUG-13: Orphan File Cleanup

**File:** `app/Models/PembelianPembayaran.php`, `app/Models/PenjualanPembayaran.php`

**Perbaikan:**

```php
static::deleting(function (PembelianPembayaran $pembayaran): void {
    if ($pembayaran->foto_dokumen) {
        foreach ($pembayaran->foto_dokumen as $file) {
            Storage::disk('public')->delete($file);
        }
    }
});
```

**Risiko:** Rendah. Menambahkan cleanup.

---

## Ringkasan Timeline

| Fase | Estimasi | Bug | Risiko |
|------|----------|-----|--------|
| **Fase 1** | 1-2 hari | BUG-03, BUG-04, BUG-06 | Rendah |
| **Fase 2** | 3-5 hari | BUG-01, BUG-02, BUG-09 | Tinggi |
| **Fase 3** | 2-3 hari | BUG-05, BUG-07, BUG-08 | Sedang |
| **Fase 4** | 3-4 hari | BUG-10, BUG-11, BUG-12, BUG-13 | Sedang |
| **Total** | **9-14 hari** | 13 bug | — |

---

## Checklist Testing

Setiap fase harus melewati:

- [ ] Unit test untuk model events
- [ ] Integration test untuk setiap flow (beli → jual → opname → adjust)
- [ ] Concurrent test (min 10 thread) untuk race condition
- [ ] Regression test untuk fitur existing
- [ ] Manual QA di staging environment

---

## Rollback Plan

Setiap fase harus punya rollback plan:

1. **Fase 1:** Git revert, tidak ada perubahan struktur DB
2. **Fase 2:** Perlu migration rollback untuk backfill qty_sisa dari StockBatch
3. **Fase 3:** Git revert, tidak ada perubahan struktur DB
4. **Fase 4:** Git revert, kecuali BUG-11 yang menambah data RMA

---

*Dokumen ini adalah rencana perbaikan. Implementasi dimulai setelah review dan persetujuan.*