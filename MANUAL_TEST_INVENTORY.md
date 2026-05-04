# 🧪 Panduan Test Manual: Penjualan & Inventory

> **Tujuan:** Memastikan alur Pembelian → Stok → Penjualan bekerja dengan akurat dan tanpa bug.

---

## 🗺️ Alur Sistem (Baca Dulu!)

```
[Pembelian] → StockBatch dibuat → qty_available bertambah
                                          ↓
[Penjualan] → PenjualanItem dibuat → qty_available berkurang
                                          ↓
[RMA]        → qty_available bertambah (kembali)
[Opname]     → qty_available dikoreksi manual
[Adjustment] → qty_available diubah manual
```

Semua perubahan stok selalu dicatat di tabel `stock_mutations` sebagai audit trail.

---

## 📋 Persiapan Sebelum Test

### 1. Backup database
```bash
mysqldump -u root -p arabica > backup_sebelum_test_$(date +%Y%m%d).sql
```

### 2. Cek kondisi awal stok
Jalankan query ini di database tool (TablePlus / phpMyAdmin):

```sql
SELECT 
    p.nama_produk,
    pi.kondisi,
    pi.id_pembelian_item,
    sb.qty_available   AS stok_siap,
    pi.qty_sisa        AS qty_sisa_lama,
    pb.no_po
FROM stock_batches sb
JOIN tb_pembelian_item pi ON pi.id_pembelian_item = sb.pembelian_item_id
JOIN md_produk p ON p.id = pi.id_produk
JOIN tb_pembelian pb ON pb.id_pembelian = pi.id_pembelian
WHERE sb.qty_available > 0
ORDER BY p.nama_produk, sb.id
LIMIT 20;
```

> **Catat:** Pilih 1 produk dari hasil query di atas sebagai "produk uji coba".  
> Contoh: `Intel Core i7-14700K`, kondisi `Baru`, `id_pembelian_item = 3`, stok = **9**

---

## 🔬 TEST A: Penjualan Normal (Happy Path)

### Tujuan
Memastikan membuat penjualan mengurangi stok dengan benar.

---

### LANGKAH A-1: Catat stok awal produk uji

```sql
-- Ganti [ID_PEMBELIAN_ITEM] dengan id yang dicatat di Persiapan
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];

-- Catat hasilnya, misal: 9
```

**Catatan saya:** Stok awal = `____`

---

### LANGKAH A-2: Buat Penjualan Baru

1. Buka **Transaksi → Input Penjualan**
2. Klik **+ Tambah Penjualan**
3. Isi form:

| Field | Isi |
|-------|-----|
| Tanggal Penjualan | Hari ini (sudah terisi) |
| Karyawan | Pilih karyawan mana saja |
| Member | Pilih member mana saja |

4. Di seksi **Daftar Produk**, klik **Tambah Produk**
5. Isi baris produk:

| Field | Yang harus terjadi |
|-------|-------------------|
| **Produk** | Pilih produk uji (misal: Intel Core i7-14700K) |
| **Kondisi** | Otomatis terisi "Baru" — **verifikasi ini benar** |
| **Batch** | Otomatis terpilih batch dengan stok — **verifikasi ini muncul** |
| **Qty** | Ketik `1` lalu klik area lain (blur) |
| **Placeholder Qty** | Harus tampil `Stok: 9` (atau sesuai stok awal) |
| **HPP** | Harus otomatis terisi — **verifikasi tidak kosong** |
| **Harga** | Harus otomatis terisi — **verifikasi tidak kosong** |

6. Lihat seksi **Grand Total** — catat nilainya:

**Grand Total = `Rp ____.____.___`**

7. Di seksi **Pembayaran**, klik **Tambah Pembayaran**
8. Isi baris pembayaran:

| Field | Isi |
|-------|-----|
| Tanggal | Hari ini |
| Metode | Tunai |
| Jumlah | Ketik angka sesuai Grand Total (misal: 7500000) |

9. Klik **Simpan Final** di pojok kanan atas

---

### LANGKAH A-3: Verifikasi Hasil Penjualan

**Yang harus terjadi setelah klik Simpan Final:**
- ✅ Notifikasi hijau muncul: *"Penjualan Final berhasil dibuat"*
- ✅ Halaman berpindah ke View Penjualan (detail nota)
- ✅ No. Nota terisi (misal: `PJ-202604-XXX`)
- ✅ Status badge: **FINAL**
- ✅ Status pembayaran: **LUNAS** (bukan TEMPO)
- ✅ Grand Total di view = Grand Total yang dihitung di form

**Catat No. Nota:** `______________`

---

### LANGKAH A-4: Verifikasi Stok Berkurang

Kembali ke database, jalankan query:

```sql
-- Cek stok setelah penjualan
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Ekspektasi: berkurang 1 (dari 9 menjadi 8)
```

**Catat:** Stok sesudah = `____`  
**Selisih = stok awal - stok sesudah = `____` (harus = qty yang dijual)**

```sql
-- Cek audit trail (stock_mutations)
SELECT id, type, qty_change, reference_type, reference_id, created_at
FROM stock_mutations
WHERE reference_type = 'PenjualanItem'
ORDER BY created_at DESC
LIMIT 5;
-- Ekspektasi: ada record dengan type='sale', qty_change = -1
```

```sql
-- Cek di UI Stock Ready
-- Buka Inventori → Stock Ready, cari produk uji
-- Verifikasi kolom "Stok" sesuai dengan qty_available di DB
```

**✅ TEST A LULUS jika:** stok berkurang tepat sebanyak qty yang dijual.

---

## 🔬 TEST B: Validasi Stok Tidak Cukup

### Tujuan
Memastikan sistem menolak penjualan jika qty melebihi stok.

---

### LANGKAH B-1: Cari stok saat ini

```sql
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Misal hasilnya: 8
```

---

### LANGKAH B-2: Coba input qty melebihi stok

1. Buka **+ Tambah Penjualan**
2. Isi Karyawan & Member
3. Tambah produk yang sama
4. Di field **Qty**, ketik angka yang LEBIH BESAR dari stok (misal: `999`)
5. Klik area lain (blur)

**Yang harus terjadi:**
- ✅ Error merah muncul langsung di bawah field Qty: `"Stok tidak cukup! Maksimal X unit."`
- ✅ Tombol Simpan Final masih bisa diklik tapi akan ditolak

6. Klik **Simpan Final**

**Yang harus terjadi:**
- ✅ Notifikasi merah: *"Validasi Gagal - Stok Tidak Cukup"*
- ✅ Penjualan **TIDAK tersimpan**
- ✅ Halaman tetap di form (tidak redirect)

```sql
-- Verifikasi stok tidak berubah
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Ekspektasi: sama dengan sebelumnya (8), tidak berkurang
```

**✅ TEST B LULUS jika:** sistem menolak dan stok tidak berubah.

---

## 🔬 TEST C: Validasi Duplikat Produk

### Tujuan
Memastikan 1 nota tidak bisa berisi produk yang sama dua kali.

---

### LANGKAH:

1. Buat Penjualan baru
2. Isi Karyawan & Member
3. Tambah produk (misal: Intel Core i7-14700K, qty=1)
4. Klik **Tambah Produk** lagi
5. Pilih **produk yang sama** (Intel Core i7-14700K), qty=1
6. Klik **Simpan Final**

**Yang harus terjadi:**
- ✅ Notifikasi merah: *"Validasi Gagal - Duplikat Produk"*
- ✅ Penjualan tidak tersimpan

**✅ TEST C LULUS jika:** sistem menolak dan menampilkan pesan duplikat.

---

## 🔬 TEST D: Penjualan Tempo (Pembayaran Sebagian)

### Tujuan
Memastikan status TEMPO muncul jika pembayaran belum lunas.

---

### LANGKAH:

1. Buat Penjualan baru (produk qty=1, harga misal Rp 7.500.000)
2. Di seksi **Pembayaran**, isi jumlah **separuh** dari Grand Total (misal: Rp 3.000.000)
3. Klik **Simpan Final**

**Yang harus terjadi:**
- ✅ Tersimpan berhasil
- ✅ Di view detail: status pembayaran = **TEMPO**
- ✅ Kolom "Sisa Bayar" di list = Rp 4.500.000 (selisih)
- ✅ Stok tetap berkurang (1 unit)

```sql
-- Verifikasi stok tetap berkurang meski tempo
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Ekspektasi: berkurang 1 dari sebelumnya
```

**✅ TEST D LULUS jika:** status TEMPO, stok tetap berkurang.

---

## 🔬 TEST E: Hapus Penjualan → Stok Kembali

### Tujuan
Memastikan menghapus penjualan mengembalikan stok.

---

### LANGKAH E-1: Catat stok sebelum

```sql
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Catat: misal 7
```

### LANGKAH E-2: Hapus penjualan dari TEST D

1. Buka **Input Penjualan** → list
2. Cari nota dari TEST D
3. Klik **⋮ → Hapus**
4. Konfirmasi

**Yang harus terjadi:**
- ✅ Modal konfirmasi muncul dengan teks "Apakah Anda yakin..."
- ✅ Setelah konfirmasi: notifikasi hijau
- ✅ Record hilang dari list

### LANGKAH E-3: Verifikasi stok kembali

```sql
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Ekspektasi: kembali ke 7 (sebelum TEST D)
```

```sql
-- Cek mutation (harus ada record reversal)
SELECT * FROM stock_mutations
WHERE reference_type IN ('Penjualan', 'PenjualanItem')
ORDER BY created_at DESC LIMIT 5;
```

**✅ TEST E LULUS jika:** stok kembali ke nilai sebelum penjualan.

---

## 🔬 TEST F: Penjualan dari TukarTambah — Tidak Bisa Dihapus

### Tujuan
Memastikan penjualan yang terikat Tukar Tambah tidak bisa dihapus.

---

### LANGKAH:

1. Buka **Input Penjualan** → list
2. Cari nota yang ada di kolom Tukar Tambah (jika ada)
3. Klik **⋮ → Hapus**

**Yang harus terjadi:**
- ✅ Modal muncul dengan header: **"Tidak Bisa Dihapus"**
- ✅ Isi modal menjelaskan alasan (terikat TT)
- ✅ Tombol hapus tidak ada / tidak aktif
- ✅ Hanya ada tombol **"Tutup"**

**✅ TEST F LULUS jika:** sistem memblokir hapus dan menjelaskan alasannya.

---

## 🔬 TEST G: Stock Ready — Verifikasi Tampilan

### Tujuan
Memastikan UI Inventory menampilkan data yang akurat dari DB.

---

### LANGKAH:

1. Buka **Inventori → Stock Ready**
2. Perhatikan kolom **Stok** untuk produk uji

```sql
-- Bandingkan dengan DB
SELECT 
    p.nama_produk,
    SUM(sb.qty_available) AS total_stok
FROM stock_batches sb
JOIN tb_pembelian_item pi ON pi.id_pembelian_item = sb.pembelian_item_id
JOIN md_produk p ON p.id = pi.id_produk
WHERE sb.qty_available > 0
GROUP BY p.id, p.nama_produk
ORDER BY p.nama_produk;
```

**✅ TEST G LULUS jika:** angka di UI sama dengan SUM dari DB.

---

## 🔬 TEST H: Stock Opname

### Tujuan
Koreksi stok manual via opname.

---

### LANGKAH:

1. Catat stok produk uji saat ini (misal: 8)
2. Buka **Inventori → Stock Opname** → Buat baru
3. Pilih produk & batch yang sama
4. **Stok Sistem** akan muncul otomatis (8)
5. Isi **Stok Fisik** = 10 (lebih besar)
6. Simpan → Posting

```sql
-- Verifikasi
SELECT qty_available FROM stock_batches
WHERE pembelian_item_id = [ID_PEMBELIAN_ITEM];
-- Ekspektasi: 10

SELECT type, qty_change FROM stock_mutations
WHERE type = 'opname' ORDER BY created_at DESC LIMIT 1;
-- Ekspektasi: qty_change = +2
```

**✅ TEST H LULUS jika:** stok berubah ke nilai fisik dan mutation tercatat.

---

## 📊 Checklist Akhir

| Test | Deskripsi | Status |
|------|-----------|--------|
| ☐ A | Penjualan normal → stok berkurang | |
| ☐ B | Qty > stok → ditolak | |
| ☐ C | Produk duplikat di 1 nota → ditolak | |
| ☐ D | Pembayaran sebagian → status TEMPO | |
| ☐ E | Hapus penjualan → stok kembali | |
| ☐ F | Penjualan TT → tidak bisa dihapus | |
| ☐ G | UI Stock Ready akurat vs DB | |
| ☐ H | Opname koreksi stok benar | |

---

## 🐛 Troubleshooting

| Gejala | Kemungkinan Penyebab | Solusi |
|--------|----------------------|--------|
| Stok tidak berkurang setelah penjualan | Observer PenjualanItem tidak berjalan | Cek `PenjualanItemObserver`, cek log |
| Status tetap TEMPO meski bayar lunas | `grand_total` di DB salah | Cek `recalculateTotals()` dipanggil |
| Field Qty tidak show placeholder stok | `getAvailableQty()` return 0 | Cek `StockBatch` ada datanya |
| UI Stock berbeda dari DB | Cache belum di-clear | `php artisan cache:clear` |
| Sync error | StockBatch belum dibuat | `php artisan inventory:sync-stock-batch` |
