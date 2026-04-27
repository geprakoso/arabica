# 🧪 Panduan Test Manual Modul Inventory

## 📋 Status Data Saat Ini

```
Produk: 13 item
Pembelian: 14 transaksi  
StockBatch: 14 batch (sudah di-sync)
```

---

## 🔍 STEP 0: Verifikasi Data Awal

Jalankan query ini di database Anda (phpMyAdmin/Sequel Pro/TablePlus):

```sql
-- Cek apakah StockBatch sinkron dengan PembelianItem
SELECT 
    pi.id_pembelian_item,
    p.nama_produk,
    pi.qty_sisa,
    sb.qty_available,
    pi.qty_sisa - sb.qty_available as diff
FROM tb_pembelian_item pi
JOIN md_produk p ON p.id = pi.id_produk
LEFT JOIN stock_batches sb ON sb.pembelian_item_id = pi.id_pembelian_item
ORDER BY diff DESC;
```

**Ekspektasi:** Kolom `diff` harus 0 untuk semua row (atau null kalau batch belum ada).

---

## ✅ TEST 1: Stock Opname (Penambahan Stok)

### Langkah UI:
1. Buka menu **Inventori → Stock Opname**
2. Klik **"Buat Stock Opname"**
3. Pilih tanggal hari ini
4. Pilih produk yang ada stok (contoh: Intel Core i7-14700K)
5. Pilih batch pembelian
6. Catat **Stok Sistem** yang muncul (misal: 20)
7. Isi **Stok Fisik** lebih besar dari sistem (misal: 23)
8. Klik **Simpan**
9. Kembali ke list, klik **Posting**
10. Perhatikan modal summary (Total Item, Selisih Positif, dll)
11. Klik **Ya, Posting**

### Verifikasi Database (Setelah Posting):
```sql
-- Cek StockBatch berkurang/tambah
SELECT * FROM stock_batches 
WHERE pembelian_item_id = [ID_BATCH_YANG_DIPILIH];
-- Ekspektasi: qty_available = 23 (dari 20)

-- Cek audit trail
SELECT * FROM stock_mutations 
WHERE reference_type = 'StockOpname' 
ORDER BY created_at DESC LIMIT 5;
-- Ekspektasi: Ada record type='opname', qty_change=+3

-- Cek status opname
SELECT * FROM tb_stock_opname ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: status = 'posted', posted_at tidak null
```

---

## ✅ TEST 2: Stock Opname (Pengurangan Stok)

### Langkah UI:
1. Buat Stock Opname baru
2. Pilih produk & batch yang sama
3. Isi **Stok Fisik** lebih kecil dari sistem (misal: 18)
4. Simpan & Posting

### Verifikasi Database:
```sql
-- Cek StockBatch
SELECT qty_available FROM stock_batches 
WHERE pembelian_item_id = [ID_BATCH];
-- Ekspektasi: 18 (dari 23, berkurang 5)

-- Cek mutation
SELECT * FROM stock_mutations 
WHERE type = 'opname' 
ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: qty_change = -5
```

---

## ✅ TEST 3: Stock Opname (Selisih 0)

### Langkah UI:
1. Buat Stock Opname baru
2. Isi Stok Fisik = Stok Sistem (sama persis)
3. Simpan & Posting

### Verifikasi Database:
```sql
-- Cek mutation
SELECT * FROM stock_mutations 
WHERE type = 'opname' 
ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: TIDAK ADA record baru (selisih 0 di-skip)
```

---

## ✅ TEST 4: Stock Opname Validasi (RMA Aktif)

### Siapkan Data:
```sql
-- Cari batch yang ada stok
SELECT pi.id_pembelian_item, p.nama_produk, sb.qty_available
FROM tb_pembelian_item pi
JOIN md_produk p ON p.id = pi.id_produk
JOIN stock_batches sb ON sb.pembelian_item_id = pi.id_pembelian_item
WHERE sb.qty_available > 0
LIMIT 1;

-- Catat id_pembelian_item-nya, lalu buat RMA:
INSERT INTO tb_rma (id_pembelian_item, status_garansi, rma_di_mana, tanggal, created_at, updated_at)
VALUES ([ID_TADI], 'di_packing', 'supplier', NOW(), NOW(), NOW());
```

### Langkah UI:
1. Buat Stock Opname
2. Pilih batch yang barusan dibuatkan RMA
3. Isi Stok Fisik berbeda dari Stok Sistem
4. Simpan & coba Posting

### Ekspektasi:
- ❌ **Gagal!** Muncul toast merah: *"Batch sedang dalam proses RMA aktif..."*
- Stok **tidak berubah**
- Status opname tetap **draft**

### Bersihkan:
```sql
-- Hapus RMA test
DELETE FROM tb_rma WHERE id_pembelian_item = [ID];
```

---

## ✅ TEST 5: Stock Adjustment (Penambahan)

### Langkah UI:
1. Buka **Inventori → Stock Adjustment**
2. Klik **Buat Stock Adjustment**
3. Pilih produk & batch
4. Isi **Qty** positif (misal: +5)
5. Keterangan: "Tambah stok testing"
6. Simpan & Posting

### Verifikasi Database:
```sql
SELECT qty_available FROM stock_batches WHERE pembelian_item_id = [ID];
-- Ekspektasi: Bertambah 5

SELECT * FROM stock_mutations WHERE type = 'adjustment' ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: qty_change = +5
```

---

## ✅ TEST 6: Stock Adjustment (Pengurangan)

### Langkah UI:
1. Buat Stock Adjustment baru
2. Isi **Qty** negatif (misal: -3)
3. Keterangan: "Kurang stok testing"
4. Simpan & Posting

### Verifikasi:
- Stok berkurang 3
- Mutation tercatat: qty_change = -3

---

## ✅ TEST 7: Stock Adjustment Validasi (Stok Negatif)

### Langkah UI:
1. Pilih batch dengan stok kecil (misal: 2 unit)
2. Buat Adjustment dengan qty = -5
3. Coba Posting

### Ekspektasi:
- ❌ **Gagal!** Toast merah: *"Adjustment mengakibatkan stok negatif..."*
- Stok **tidak berubah** (atomic rollback)

---

## ✅ TEST 8: RMA Auto-Return Stok

### Langkah UI:
1. Buka **Inventori → RMA**
2. Klik **Buat RMA**
3. Pilih batch yang ada stok
4. Status: **di_packing**
5. Simpan
6. Edit RMA → Ubah status jadi **selesai**
7. Simpan

### Verifikasi Database (Sebelum & Sesudah):
```sql
-- Sebelum update status (catat dulu)
SELECT qty_available FROM stock_batches WHERE pembelian_item_id = [ID];
-- Misal: 10

-- Setelah update status ke 'selesai'
SELECT qty_available FROM stock_batches WHERE pembelian_item_id = [ID];
-- Ekspektasi: 11 (bertambah 1)

-- Cek mutation RMA
SELECT * FROM stock_mutations WHERE type = 'rma_return' ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: qty_change = +1, reference_type = 'Rma'
```

---

## ✅ TEST 9: Inventory Resource (Tampilan)

### Langkah UI:
1. Buka **Inventori → Stock Ready**
2. Perhatikan kolom **Stok** — harus sama dengan StockBatch.qty_available
3. Klik **Filter** → pilih **Status Stok** → **Low Stock**
4. Harus muncul produk dengan stok 1-10
5. Klik **Detail** (icon mata) pada salah satu produk
6. Perhatikan **Batch Pembelian Aktif** — harus sesuai dengan stock_batches

### Verifikasi Database:
```sql
-- Bandingkan tampilan UI dengan query ini:
SELECT 
    p.nama_produk,
    p.sku,
    sb.qty_available,
    pi.qty_sisa
FROM md_produk p
JOIN stock_batches sb ON sb.produk_id = p.id
JOIN tb_pembelian_item pi ON pi.id_pembelian_item = sb.pembelian_item_id
WHERE sb.qty_available > 0
ORDER BY p.nama_produk;

-- Ekspektasi: qty_available (UI) == sb.qty_available (DB)
```

---

## ✅ TEST 10: Atomicity (Rollback Test)

### Siapkan:
Buat 2 item adjustment dalam 1 transaksi:
- Item 1: qty = -2 (valid, stok cukup)
- Item 2: qty = -999 (invalid, stok tidak cukup)

### Langkah:
1. Buat Stock Adjustment
2. Tambah 2 item di atas
3. Simpan & Posting

### Ekspektasi:
- ❌ **Gagal total!** (bukan partial)
- Item 1: **Tidak berkurang** (rollback)
- Item 2: **Tidak berkurang** (rollback)
- Status adjustment tetap **draft**

### Verifikasi:
```sql
SELECT qty_available FROM stock_batches WHERE id = [ITEM1_ID];
-- Ekspektasi: Sama seperti sebelum posting (tidak berubah)
```

---

## ✅ TEST 11: Filter Produk Terhapus

### Langkah UI:
1. Buka **Stock Ready**
2. Klik **Filter** → aktifkan **Tampilkan Produk Terhapus**
3. Perhatikan produk yang ada indikator `[DELETED]` atau `⚠️`

---

## ✅ TEST 12: Sync Command

### Terminal:
```bash
cd /Applications/MAMP/htdocs/arabica
php artisan inventory:sync-stock-batch --dry-run
```

### Ekspektasi:
- Kalau semua sync: *"All StockBatch are in sync"*
- Kalau ada diff: akan ditampilkan tabel perbedaannya

```bash
# Kalau mau sync (run for real):
php artisan inventory:sync-stock-batch
```

---

## 📊 Checklist Test

| Test | Status |
|------|--------|
| ☐ Opname tambah stok | |
| ☐ Opname kurang stok | |
| ☐ Opname selisih 0 (skip) | |
| ☐ Opname gagal karena RMA aktif | |
| ☐ Adjustment tambah | |
| ☐ Adjustment kurang | |
| ☐ Adjustment gagal stok negatif | |
| ☐ RMA auto-return saat selesai | |
| ☐ Inventory tampilan benar | |
| ☐ Atomic rollback | |
| ☐ Filter produk terhapus | |
| ☐ Sync command | |

---

## 🐛 Kalau Ada Error

### Error 1: "StockBatch tidak ditemukan"
```sql
-- Solusi: Sync ulang
php artisan inventory:sync-stock-batch
```

### Error 2: "Stok tidak sama antara UI dan DB"
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
```

### Error 3: "WooCommerce error saat test"
```php
// Di .env testing, pastikan:
WOOCOMMERCE_STORE_URL=
WOOCOMMERCE_CONSUMER_KEY=
WOOCOMMERCE_CONSUMER_SECRET=
// (Kosongkan kalau tidak ada)
```

---

## 💾 Backup Sebelum Test

**Sangat direkomendasikan** backup dulu:

```bash
mysqldump -u root -p arabica > backup_before_manual_test.sql
```

Atau pakai TablePlus/phpMyAdmin → Export.

---

## 🎯 Ringkasan Flow Test

```
1. Cek data awal (SQL)
2. Lakukan aksi di UI
3. Cek hasil di UI (toast, badge, tabel)
4. Verifikasi di database (SQL)
5. Kalau salah → rollback/clear data test
```

Selamat testing! 🚀
