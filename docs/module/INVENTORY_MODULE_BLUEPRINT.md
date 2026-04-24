# Blueprint Modul Inventory — Laporan Alur Bisnis & Aturan Bisnis

> **Versi:** 1.0 | April 2026  
> **Status:** Dokumentasi Resmi — Analisis Kode Saat Ini  
> **Scope:** Modul Inventory, Pembelian, Penjualan, Stok Opname, Penyesuaian Stok, RMA, Tukar Tambah

---

## 1. Entitas & Relasi Inventory

### 1.1 Diagram Entitas

```
┌──────────────┐     ┌──────────────────┐     ┌──────────────────┐
│   Produk     │────<│  PembelianItem   │>────│   Pembelian      │
│  (md_produk) │     │(tb_pembelian_item)│     │  (tb_pembelian)  │
└──────┬───────┘     └────────┬─────────┘     └──────────────────┘
       │                      │
       │           ┌──────────┼──────────┐
       │           │          │          │
       │     ┌─────┴─────┐ ┌──┴──────────┐ ┌─┴───────────┐
       │     │StockBatch  │ │PenjualanItem│ │    RMA      │
       │     │(stock_batch)│ │(tb_penjual..)│ │  (tb_rma)   │
       │     └────────────┘ └──────┬──────┘ └─────────────┘
       │                           │
       │                  ┌─────────┴──────────┐
       │                  │    Penjualan        │
       │                  │  (tb_penjualan)     │
       │                  └────────────────────┘
       │
 ├───────────────────────┤
 │                       │
┌┴──────────────┐ ┌──────┴──────────┐
│ StockOpname   │ │ StockAdjustment  │
│(stock_opnames)│ │(stock_adjustments)│
└───────┬───────┘ └────────┬─────────┘
        │                  │
┌───────┴────────┐ ┌───────┴─────────────┐
│StockOpnameItem │ │StockAdjustmentItem   │
└────────────────┘ └──────────────────────┘

┌──────────────────────────────────────────┐
│            TukarTambah                   │
│  (tb_tukar_tambah)                       │
│  Relasi: 1 Penjualan + 0..1 Pembelian    │
│  Penjualan ← TukarTambah → Pembelian    │
└──────────────────────────────────────────┘
```

### 1.2 Relasi Kunci

| Entitas | Relasi | Keterangan |
|---------|--------|------------|
| PembelianItem → Produk | N:1 | Setiap item pembelian merujuk 1 produk |
| PembelianItem → Pembelian | N:1 | Item milik 1 pembelian |
| PembelianItem → PenjualanItem | 1:N | Satu batch bisa dijual berkali-kali |
| PembelianItem → StockBatch | 1:1 | Setiap item punya 1 batch stok |
| PembelianItem → RMA | 1:N | Batch bisa punya banyak RMA aktif |
| PenjualanItem → Penjualan | N:1 | Item milik 1 penjualan |
| PenjualanItem → PembelianItem | N:1 | Item penjualan merujuk batch pembelian |
| TukarTambah → Penjualan | 1:1 | Setiap TT punya 1 penjualan |
| TukarTambah → Pembelian | 1:0..1 | TT opsional punya 1 pembelian (trade-in) |

---

## 2. Alur Bisnis Inventory

### 2.1 Alur Pembelian (Stock Masuk)

```
┌─────────────┐
│ Input PO     │
│ (Pembelian)  │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ Create PembelianItem  │
│ - qty, qty_masuk,     │
│   qty_sisa = qty      │
│ - hpp, harga_jual     │
│ - kondisi (Baru/Bekas)│
│ - subtotal = qty×hpp  │
└──────┬───────────────┘
       │ (model event: created)
       ▼
┌──────────────────────┐
│ Auto-create StockBatch│
│ - qty_total = qty     │
│ - qty_available = qty │
└───────────────────────┘
```

**Aturan:**
- R02: Produk duplikat dengan kondisi berbeda diperbolehkan (Baru vs Bekas)
- R03: Subtotal = qty × HPP (otomatis)
- R14: qty tetap, qty_sisa berkurang saat penjualan

### 2.2 Alur Penjualan (Stock Keluar)

```
┌──────────────────┐     ┌──────────────────────┐
│ Penjualan Manual │     │    POS Checkout        │
│ (Filament Form)  │     │ (CheckoutPosAction)    │
└───────┬──────────┘     └──────────┬──────────────┘
        │                            │
        │  ┌─────────────────────────┘
        │  │
        ▼  ▼
┌──────────────────────────┐
│  FIFO Batch Allocation    │
│  1. Lock batch (lockFor   │
│     Update)               │
│  2. Cek stok tersedia     │
│  3. Allocasi per batch    │
│  4. Create PenjualanItem  │
└──────────┬───────────────┘
           │ (model event: created)
           ▼
┌──────────────────────────┐
│  PenjualanItem::created   │
│  - assertStockAvailable() │
│  - applyStockMutation()   │
│    (decrement qty_sisa)   │
│  - recalculateTotals()    │
└───────────────────────────┘
```

**Aturan:**
- R17: Pessimistic locking wajib saat akses batch stok
- Stok keluar mengurangi PembelianItem.qty_sisa
- HPP selalu disinkronkan dari batch ke PenjualanItem

### 2.3 Alur Tukar Tambah

```
┌───────────────────────────────┐
│       Tukar Tambah            │
│  (1 transaksi = 2 arah)       │
├───────────────────────────────┤
│  Penjualan (jual barang baru) │──→ Stock KELUAR (via PenjualanItem)
│  Pembelian  (beli barang     │──→ Stock MASUK  (via PembelianItem)
│              bekas/trade-in)  │
└───────────────────────────────┘
```

**Aturan:**
- Hapus TT → cascade hapus Penjualan & Pembelian terkait
- Pembelian TT tidak bisa dihapus jika item-nya sudah dipakai transaksi lain
- Statis flag `$allowTukarTambahDeletion` mengatur cascade deletion

### 2.4 Alur Stock Opname

```
┌──────────────┐     ┌─────────────────┐     ┌──────────────┐
│ Create Draft  │────>│ Input Stok      │────>│   POSTING     │
│ (kode auto)   │     │ Sistem vs Fisik │     │  (irreversible)│
└──────────────┘     └─────────────────┘     └───────┬──────┘
                                                       │
                                              ┌────────▼────────┐
                                              │ applyToBatch()  │
                                              │ qty_sisa +=     │
                                              │   selisih       │
                                              └─────────────────┘
```

**Aturan:**
- Draft bisa diedit, Posted tidak bisa diedit/dihapus
- selisih = stok_fisik - stok_sistem (otomatis)
- Posting mengubah qty_sisa pada PembelianItem

### 2.5 Alur Stock Adjustment

```
┌──────────────┐     ┌─────────────────┐     ┌──────────────┐
│ Create Draft  │────>│ Input produk +  │────>│   POSTING     │
│ (kode auto)   │     │ qty (+/-)       │     │  (irreversible)│
└──────────────┘     └─────────────────┘     └───────┬──────┘
                                                       │
                                              ┌────────▼────────┐
                                              │ applyToBatch()  │
                                              │ qty_sisa += qty │
                                              └─────────────────┘
```

**Aturan:**
- Qty bisa positif (tambah) atau negatif (kurang)
- Draft bisa diedit, Posted tidak bisa diedit/dihapus
- ⚠️ Tidak ada validasi qty_sisa tidak boleh negatif

### 2.6 Alur RMA (Return Merchandise Authorization)

```
┌──────────────┐     ┌────────────────────┐     ┌──────────────┐
│ Create RMA    │────>│ Status: di_packing  │────>│ Status:       │
│               │     │  atau proses_klaim │     │ selesai       │
└──────────────┘     └────────────────────┘     └──────────────┘
```

**Aturan:**
- Batch dengan RMA aktif tidak bisa dijual (dicegah di PenjualanItem::assertStockAvailable)
- RMA aktif = status `di_packing` atau `proses_klaim`
- Satu batch hanya bisa punya 1 RMA aktif

### 2.7 Alur Lock Final Pembelian

```
┌─────────────┐     ┌──────────────┐
│ Pembelian    │────>│  LOCK FINAL  │
│ (editable)   │     │ (irreversible)│
└─────────────┘     └──────────────┘
```

- R16: Setelah di-lock, data tidak bisa diedit lagi
- Grand total disimpan sebelum lock

---

## 3. Keputusan Desain: Dual Stock Mechanism

> **STATUS: KRITIS — Memerlukan Resolusi**

### 3.1 Kondisi Saat Ini

Sistem saat ini mengelola stok melalui **DUA mekanisme paralel**:

| Mekanisme | Field | Digunakan di | Status |
|-----------|-------|-------------|--------|
| `PembelianItem.qty_sisa` | Kolom di tb_pembelian_item | Penjualan, POS, Inventory view, Stock Opname, Adjustment | ✅ Aktif |
| `StockBatch.qty_available` | Tabel stock_batches (terpisah) | Hanya pencatatan saat create PembelianItem | ⚠️ Tidak pernah di-decrement |

**Akibat:** StockBatch.qty_available TIDAK PERNAH berkurang saat penjualan. Data di StockBatch menjadi false constellation karena hanya di-set saat create dan tidak dimutasi.

### 3.2 Rekomendasi

Pilih salah satu:

- **Opsi A (Recommended):** Jadikan StockBatch sebagai single source of truth, hapus ketergantungan pada PembelianItem.qty_sisa
- **Opsi B:** Hapus StockBatch sepenuhnya, pertahankan qty_sisa sebagai mekanisme utama

---

## 4. Daftar Aturan Bisnis (Business Rules)

### 4.1 Aturan yang Sudah Terimplementasi

| Kode | Aturan | Status | Lokasi Kode |
|------|--------|--------|-------------|
| R01 | Sistem batch (bukan FIFO/LIFO formal) | ✅ | StockBatch model |
| R02 | Produk duplikat dengan kondisi berbeda diperbolehkan | ✅ | PembelianItem::creating (duplikat check) |
| R03 | Subtotal = qty × HPP (auto-calculate) | ✅ | PembelianItem::creating/updating |
| R04 | SN & Garansi diganti subtotal | ✅ | Kolom serials opsional, tidak wajib |
| R05 | Pembelian boleh hanya jasa tanpa produk | ✅ | Tidak ada validasi wajib item produk |
| R06 | Hanya 2 status pembayaran: TEMPO/LUNAS | ✅ | Pembelian::getStatusAttribute |
| R07 | Status pembayaran otomatis berdasarkan total paid | ✅ | PembelianItem::saved → recalculatePaymentStatus |
| R08 | Kelebihan pembayaran ditampilkan | ✅ | Pembelian::getKelebihanAttribute |
| R09 | Lock section item saat edit Pembelian | ✅ | Pembelian::isEditLocked |
| R10 | Edit Pembelian hanya untuk jumlah pembayaran | ✅ | Implementasi di Filament resource |
| R11 | Grand total disimpan di database | ✅ | Kolom grand_total di tb_pembelian |
| R12 | Cegah hapus Pembelian jika item dipakai Penjualan | ✅ | Pembelian::deleting |
| R13 | Larangan hapus PO yang punya NO TT | ✅ | Pembelian::canDelete |
| R14 | Qty tetap (qty_masuk), qty_sisa berkurang | ✅ | Kolom qty_masuk vs qty_sisa |
| R16 | Lock Final (irreversible) | ✅ | Pembelian::lockFinal |
| R17 | Pessimistic locking untuk stok batch | ⚠️ Parsial | StockBatch::decrementWithLock TIDAK DIGUNAKAN |
| BR-INV-01 | Stok RMA aktif tidak bisa dijual | ✅ | PenjualanItem::assertStockAvailable |
| BR-INV-02 | Penjualan otomatis pilih batch FIFO | ✅ | CreatePenjualan::createItemsWithFifo |
| BR-INV-03 | Duplikat produk+batch+kondisi dicegah di penjualan | ✅ | CreatePenjualan::validateBeforeCreate |
| BR-INV-04 | Stock Opname posting mengubah qty_sisa | ✅ | StockOpnameItem::applyToBatch |
| BR-INV-05 | Stock Adjustment posting mengubah qty_sisa | ✅ | StockAdjustmentItem::applyToBatch |
| BR-INV-06 | Stok opname hanya draft yang bisa diedit | ✅ | StockOpnameResource (visible condition) |
| BR-INV-07 | Stock adjustment hanya draft yang bisa diedit | ✅ | StockAdjustmentResource (visible condition) |
| BR-INV-08 | Penjualan tidak bisa hapus jika dari TT | ✅ | Penjualan::deleting |
| BR-INV-09 | Pembelian tidak bisa hapus jika dari TT | ✅ | Pembelian::deleting |
| BR-INV-10 | Cache perhitungan dibersihkan saat data berubah | ✅ | CacheHelper::flush |

### 4.2 Aturan yang Belum/Tidak Lengkap

| Kode | Aturan | Status | Keterangan |
|------|--------|--------|------------|
| R15 | Kelola file bukti transfer (no orphan files) | ❌ Tidak terimplementasi | Tidak ada mekanisme cleanup file |
| R17 | Pessimistic locking untuk stok batch | ⚠️ Parsial | StockBatch::decrementWithLock ada tapi TIDAK DIGUNAKAN di flow penjualan |
| BR-INV-11 | Stock Adjustment tidak boleh membuat qty negatif | ❌ Tidak ada validasi | applyToBatch bisa membuat qty_sisa < 0 |
| BR-INV-12 | Stock Opname/Adjustment posting harus atomic | ❌ Tidak di DB transaction | Jika gagal di tengah, data tidak konsisten |
| BR-INV-13 | RMA status selesai harus mengembalikan stok | ❌ Tidak terimplementasi | Tidak ada hook saat RMA selesai |
| BR-INV-14 | StockBatch harus konsisten dengan qty_sisa | ❌ Drift | StockBatch.qty_available tidak pernah di-decrement |

---

## 5. Analisis Bug & Celah

### 🔴 KRITIS

#### BUG-01: Dual Stock Tracking — StockBatch divergen dari qty_sisa

**Lokasi:** `PembelianItem::created` (line 108-117), `PenjualanItem::created` (line 46-49)

**Masalah:** StockBatch dibuat saat PembelianItem dibuat dengan `qty_available = qty_sisa`, tetapi saat PenjualanItem mengurangi stok, hanya `PembelianItem.qty_sisa` yang dikurangi via `applyStockMutation()`. StockBatch.qty_available tidak pernah di-decrement.

**Dampak:** Data StockBatch menunjukkan stok lebih besar dari aktual. Jika kode di masa depan menggunakan StockBatch sebagai sumber data, akan terjadi oversell.

**Rekomendasi:** Pilih salah satu: (A) Jadikan StockBatch sebagai single source of truth dan gunakan `StockBatch::decrementWithLock()` di semua flow penjualan, atau (B) Hapus migrasi dan model StockBatch.

---

#### BUG-02: Race Condition pada Stok — Penjualan Manual tanpa Locking

**Lokasi:** `PenjualanItem::applyStockMutation()` (line 125-142)

**Masalah:** Fungsi ini melakukan READ → MODIFY → WRITE pada `PembelianItem.qty_sisa` tanpa `lockForUpdate()` atau DB transaction. Sementara, POS Checkout dan manual Penjualan Create menggunakan `lockForUpdate()` di level controller, tapi PenjualanItem model event TIDAK menggunakan locking.

**Skenario:**
1. Kasir A dan B sama-sama baca batch qty_sisa = 5
2. Keduanya buat PenjualanItem dengan qty = 3
3. Kedua model event `created` menjalankan `applyStockMutation` bergantian
4. Hasil: qty_sisa = 5 - 3 - 3 = -1 (oversell)

**Dampak:** Stok bisa negatif, oversell terjadi saat concurrent transactions.

**Rekomendasi:** Bungkus `applyStockMutation` dalam DB transaction dengan `lockForUpdate()` pada batch, atau gunakan atomic `decrement()` query.

---

#### BUG-03: Stock Opname/Adjustment Posting Tidak Atomic

**Lokasi:** `StockOpname::post()` (line 84-96), `StockAdjustment::post()` (line 81-96)

**Masalah:** Kedua method melakukan loop dan memanggil `applyToBatch()` per item tanpa DB transaction. Jika item ke-3 gagal, item 1 dan 2 sudah ter-apply, menyebabkan data tidak konsisten.

**Rekomendasi:** Bungkus seluruh posting dalam `DB::transaction()`.

---

#### BUG-04: Stock Adjustment Bisa Membuat Stok Negatif

**Lokasi:** `StockAdjustmentItem::applyToBatch()` (line 35-45)

**Masalah:** Tidak ada validasi bahwa `qty_sisa + adjustment_qty >= 0`. User bisa membuat adjustment dengan qty negatif yang lebih besar dari stok tersedia.

```php
// Saat ini:
$batch->{$qtyColumn} = max(0, (int) ($batch->{$qtyColumn} ?? 0) + (int) $this->qty);
// max(0, ...) mencegah negatif di database, tapi data jadi TIDAK AKURAT
```

`max(0, ...)` mencegah nilai negatif tapi menyembunyikan selisih. Stok yang seharusnya -2 menjadi 0, artinya ada 2 unit yang "hilang".

**Rekomendasi:** Tambahkan validasi sebelum apply bahwa `qty_sisa + adjustment_qty >= 0`.

---

### 🟡 SEDANG

#### BUG-05: Kontradiksi Policy R01 vs Implementasi FIFO

**Lokasi Policy:** `PURCHASE_MODULE_POLICY_1.md` R01: "FIFO tidak digunakan"

**Lokasi Kode:** 
- `CheckoutPosAction::fulfillItemUsingFifo()` (line 144-199)
- `ItemsRelationManager::fulfillUsingFifo()` (line 278-339)
- `CreatePenjualan::createItemsWithFifo()` (line 360-471)

**Masalah:** Kode secara konsisten menggunakan FIFO (orderBy id_pembelian_item) untuk alokasi batch, bertentangan dengan policy yang menyatakan FIFO tidak digunakan.

**Rekomendasi:** Perbarui policy jika FIFO memang diinginkan, atau ubah logika alokasi batch.

---

#### BUG-06: Duplicate creating Event di PembelianItem

**Lokasi:** `PembelianItem::booted()` line 42-83 dan line 97-105

**Masalah:** Dua callback `creating` terdaftar. Callback kedua (line 97-105) membungkus try/catch untuk `UniqueConstraintViolationException` tapi tidak menjalankan operasi database apapun di dalam try block. Ini adalah dead code.

```php
// Line 97-105 (DEAD CODE):
static::creating(function (PembelianItem $item) {
    try {
        // Validasi sudah dilakukan di atas
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
        throw ValidationException::withMessages([...]);
    }
});
```

**Rekomendasi:** Hapus callback creating kedua (dead code).

---

#### BUG-07: Cache Flush Terlalu Agresif

**Lokasi:** `CacheHelper::flush()`, `Pembelian::clearCalculationCache()`

**Masalah:** Setiap perubahan pada SATU Pembelian meng-flush cache SELURUH tag Pembelian. Ini menyebabkan cache miss untuk semua data pembelian, bukan hanya yang berubah.

**Dampak:** Performa menurun saat banyak concurrent requests.

**Rekomendasi:** Gunakan cache key spesifik per record (`arabica:calc:pembelian:{id}`) dan invalidasi per-key, bukan per-tag.

---

#### BUG-08: TukarTambah Static Flag Tidak Thread-Safe

**Lokasi:** `TukarTambah::deleting()` (line 86-96)

**Masalah:** `Penjualan::$allowTukarTambahDeletion` dan `Pembelian::$allowTukarTambahDeletion` adalah static variable yang di-set `true` lalu `false` dalam satu request. Meskipun aman dalam single request, ini rentan jika ada queue job atau event listener yang berjalan async.

**Rekomendasi:** Gunakan parameter method/callback alih-alih static flag, atau pastikan operasi dalam DB transaction yang sama.

---

#### BUG-09: PenjualanItem Update — Double Stock Mutation Tanpa Transaction

**Lokasi:** `PenjualanItem::updated()` (line 51-61)

**Masalah:** Saat update PenjualanItem (misalnya ganti batch), event `updated` melakukan:
1. Restore stok batch lama: `applyStockMutation(originalBatchId, originalQty)`
2. Kurangi stok batch baru: `applyStockMutation(newBatchId, newQty)`

Kedua operasi ini dipanggil secara berurutan TANPA DB transaction. Jika operasi 2 gagal, stok batch lama sudah di-restore tapi batch baru tidak dikurangi = inkonsistensi.

**Rekomendasi:** Bungkus kedua operasi dalam `DB::transaction()`.

---

### 🟢 RENDAH

#### BUG-10: StockBatch Auto-Create Tidak Handle Update Qty

**Lokasi:** `PembelianItem::created` (line 108-117)

**Masalah:** StockBatch hanya dibuat saat PembelianItem dibuat. Jika PembelianItem qty di-update (misalnya dari 0 ke 5), tidak ada StockBatch yang dibuat. Juga, saat PembelianItem dihapus, StockBatch tidak dihapus (foreign key cascade ada di DB, tapi model event tidak trigger).

**Rekomendasi:** Tambahkan logic di `PembelianItem::updated` untuk sync StockBatch, atau migrasikan sepenuhnya ke StockBatch.

---

#### BUG-11: RMA Selesai Tidak Mengembalikan Stok

**Lokasi:** `Rma` model

**Masalah:** Saat RMA status berubah ke `selesai`, tidak ada hook yang mengembalikan stok batch (menambah qty_sisa). Barang yang diklaim garansi dan dikembalikan tidak masuk kembali ke inventory.

**Rekomendasi:** Tambahkan model event `updated` di RMA yang mengecek perubahan status ke `selesai` dan membuat Stock Adjustment otomatis untuk mengembalikan stok.

---

#### BUG-12: Inventory Resource Tidak Filter Gudang

**Lokasi:** `InventoryResource::applyInventoryScopes()` (line 336-372)

**Masalah:** Query inventory tidak memfilter berdasarkan gudang (warehouse), padahal model Gudang ada dan StockOpname/Adjustment memiliki kolom gudang_id. Semua stok dari semua gudang ditampilkan dalam satu view.

**Rekomendasi:** Tambahkan filter gudang di inventory view, atau tambahkan kolom gudang_id di PembelianItem untuk tracking gudang per batch.

---

#### BUG-13: File Dokumen Pembayaran Tidak Ada Cleanup

**Lokasi:** Terkait R15 (Policy)

**Masalah:** Saat pembayaran dihapus, file bukti transfer (foto_dokumen) yang tersimpan tidak dihapus, menciptakan orphan files.

**Rekomendasi:** Tambahkan observer/model event yang menghapus file storage saat pembayaran dihapus.

---

## 6. Alur Data Stok — Visual Summary

```
                    ┌──────────────┐
                    │  PEMBELIAN    │
                    │  (Stock IN)   │
                    └──────┬───────┘
                           │
                ┌──────────▼──────────┐
                │  PembelianItem       │
                │  qty_masuk = X       │
                │  qty_sisa  = X       │◄──── Awalnya sama
                │                      │
                │  + StockBatch        │
                │    qty_total = X      │
                │    qty_available = X │◄──── Awalnya sama
                └──────────┬──────────┘
                           │
          ┌────────────────┼────────────────┐
          │                │                │
    ┌─────▼─────┐  ┌──────▼──────┐  ┌──────▼──────┐
    │ PENJUALAN │  │  STOCK       │  │  STOCK       │
    │ (OUT)     │  │  OPNAME      │  │  ADJUSTMENT  │
    │            │  │  (ADJUST)    │  │  (ADJUST)    │
    └─────┬──────┘  └──────┬──────┘  └──────┬──────┘
          │                │                │
          │      qty_sisa += selisih    qty_sisa += qty
          │                │                │
          ▼                ▼                ▼
    ┌─────────────────────────────────────────────┐
    │         PembelianItem.qty_sisa              │
    │  (single source of truth SAAT INI)          │
    │                                             │
    │  ⚠️ StockBatch.qty_available TIDAK SINKRON  │
    └─────────────────────────────────────────────┘
```

---

## 7. Rangkuman Prioritas Perbaikan

| Prioritas | Bug | Dampak | Estimasi |
|-----------|-----|--------|----------|
| 🔴 P0 | BUG-01: Dual Stock Tracking | Data stok tidak akurat | 2-3 hari |
| 🔴 P0 | BUG-02: Race Condition Stok | Oversell, stok negatif | 1-2 hari |
| 🔴 P0 | BUG-03: Posting Non-Atomic | Data tidak konsisten | 0.5 hari |
| 🔴 P0 | BUG-04: Stok Negatif via Adjustment | Data tidak akurat | 0.5 hari |
| 🟡 P1 | BUG-05: Kontradiksi Policy FIFO | Kebingungan tim | 0.5 hari |
| 🟡 P1 | BUG-06: Dead Code creating event | Maintainability | 0.5 hari |
| 🟡 P1 | BUG-07: Cache Flush Agresif | Performa | 1 hari |
| 🟡 P1 | BUG-08: Static Flag Thread-Safety | Potensi bug async | 1 hari |
| 🟡 P1 | BUG-09: Update Mutation Tanpa TX | Inkonsistensi stok | 0.5 hari |
| 🟢 P2 | BUG-10: StockBatch Not Synced | Data drift | 1 hari |
| 🟢 P2 | BUG-11: RMA Selesai Tidak Return Stok | Stok hilang | 1 hari |
| 🟢 P2 | BUG-12: No Warehouse Filter | UX kurang | 1 hari |
| 🟢 P2 | BUG-13: Orphan File Cleanup | Storage waste | 0.5 hari |

---

## 8. Rekomendasi Arsitektur Inventory

### 8.1 Single Source of Truth: StockBatch

Rekomendasi utama adalah memigrasikan seluruh sistem stok ke menggunakan `StockBatch` sebagai single source of truth:

```php
// Target arsitektur:
// 1. Semua operasi stok melalui StockBatch
// 2. PembelianItem.qty_sisa = derived/computed (read-only)
// 3. Pessimistic locking wajib di semua write path
// 4. DB transaction wajib di semua multi-step mutation

class StockBatch {
    // Source of truth:
    // - qty_total (fixed, dari pembelian)
    // - qty_available (mutable, dikurangi saat jual, ditambah saat retur/adjustment)
    
    // Semua decrement harus melalui:
    public static function decrementWithLock(int $batchId, int $qty): bool
    
    // Semua increment harus melalui:
    public static function incrementWithLock(int $batchId, int $qty, string $reason): bool
}
```

### 8.2 Transaction Wrapper untuk Semua Stock Mutation

```php
// Semua operasi stok harus di dalam DB transaction:
DB::transaction(function () {
    $batch = StockBatch::lockForUpdate()->find($batchId);
    // validate
    // mutate
    // log
});
```

### 8.3 Audit Trail

```php
// Setiap perubahan stok wajib dicatat di log:
// - stock_mutations table: batch_id, type (sale/purchase/adjustment/opname), qty_before, qty_after, reference_type, reference_id
```

---

*Dokumen ini dibuat berdasarkan analisis kode aktual pada proyek Arabica, April 2026.*