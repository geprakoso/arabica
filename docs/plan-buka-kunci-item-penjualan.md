# Plan: Membuka Kunci Edit Item Produk & Jasa pada Penjualan

## 1. Current Behavior (Sebelum Perubahan)

### Logika Locking
```php
// Model Penjualan
public function canEditItems(): bool
{
    return $this->isDraft()
        && ! $this->is_locked
        && ! $this->items()->exists()
        && ! $this->jasaItems()->exists();
}
```

**Aturan saat ini:**
- Item **Produk** hanya bisa diubah jika dokumen masih **Draft** dan **BELUM ADA** item produk/jasa.
- Begitu user klik "Simpan" dan item tersimpan ke database, section Produk & Jasa **terkunci permanen**.
- User hanya bisa mengubah: Informasi Penjualan (member, tanggal, karyawan) dan Pembayaran.

### Flow Create vs Edit
| Step | Create Penjualan | Edit Penjualan |
|------|------------------|----------------|
| 1 | User isi items_temp di form | Form di-fill dari `mutateFormDataBeforeFill` |
| 2 | Save -> `handleRecordCreate` | Save -> `handleRecordUpdate` |
| 3 | `items()->delete()` lalu `createItemsWithFifo()` | **Skip** kalau `!canEditItems()` |
| 4 | Stok dipotong via `PenjualanItemObserver::created` | Stok tidak berubah |

---

## 2. Problem Statement

User membutuhkan fleksibilitas untuk **menambah, menghapus, atau mengubah** item produk/jasa setelah penjualan tersimpan (asalkan masih Draft & belum di-lock).

---

## 3. Efek Terburuk (Worst-Case Scenarios)

### A. Inconsistensi Stok (CRITICAL)
**Skenario:** User menghapus 1 item produk yang sudah tersimpan.

**Dampak:**
- Stok batch sudah dipotong saat create (via `PenjualanItemObserver::created`).
- Kalau item dihapus saat edit, stok **TIDAK otomatis kembali** karena `EditPenjualan::handleRecordUpdate` skip processing items.
- Stok fisik jadi lebih besar dari stok sistem.

**Contoh:**
```
Stok awal batch A: 10
Penjualan #1: beli 3 -> Stok sistem: 7 (benar)
User edit: hapus item -> Stok sistem tetap 7 (salah! harusnya 10)
Penjualan #2: beli 5 -> Stok sistem: 2 (salah! seharusnya masih 5)
```

### B. Double Dip Stok (CRITICAL)
**Skenario:** User mengganti produk dari A ke B (qty sama).

**Dampak:**
- Stok A tidak dikembalikan.
- Stok B dipotong lagi.
- Total stok yang dipotong = 2x qty.

### C. Pembayaran Tidak Sesuai Grand Total (HIGH)
**Skenario:** User menambah item sehingga grand total naik dari 1.000.000 -> 1.500.000.

**Dampak:**
- Pembayaran yang sudah tercatat tetap 1.000.000.
- Status pembayaran tetap "LUNAS" padahal seharusnya "TEMPO".
- Laporan keuangan tidak balance.

### D. Serial Number Kacau (HIGH)
**Skenario:** User mengubah qty dari 3 -> 2, tapi serial number tetap 3.

**Dampak:**
- SN yang tidak terpakai "nyangkut" di record.
- Atau SN hilang karena tidak di-sync.
- Garansi berdasarkan SN jadi tidak valid.

### E. Batch & HPP Tidak Konsisten (MEDIUM)
**Skenario:** User mengganti batch dari batch lama (HPP 100k) ke batch baru (HPP 120k).

**Dampak:**
- Laporan laba rugi per transaksi jadi salah.
- HPP di `PenjualanItem::applyBatchDefaults` baru di-apply saat create, tidak saat edit.

### F. Jasa yang Sudah Tersimpan (MEDIUM)
**Skenario:** User menghapus jasa yang sudah tersimpan.

**Dampak:**
- `PenjualanJasa` tidak punya observer seperti `PenjualanItem`.
- Tidak ada mekanisme otomatis untuk mengembalikan status jasa (misal: jasa belum terpakai).
- Relasi ke `PembelianJasa` mungkin perlu di-refresh.

### G. Tukar Tambah Terkait (CRITICAL)
**Skenario:** Penjualan ini adalah bagian dari Tukar Tambah.

**Dampak:**
- `canDelete()` sudah cek Tukar Tambah, tapi `canEditItems()` tidak.
- Perubahan item bisa merusak perhitungan selisih di Tukar Tambah.
- Harga jual barang yang ditukar mungkin tidak sesuai.

---

## 4. Solusi & Rekomendasi

### Rekomendasi Utama: **Diff-Based Reconciliation**

Daripada delete semua & recreate (seperti create), kita **bandingkan** items lama vs items baru, lalu apply perubahan secara surgical.

#### 4.1 Algoritma Diff untuk Item Produk

```
Input:  $existingItems (dari DB), $newItems (dari form items_temp)
Output: actions[] (create, update, delete)

1. Index $existingItems by "id_produk|id_pembelian_item|kondisi"
2. Index $newItems by "id_produk|id_pembelian_item|kondisi"
3. For each key in union(existing, new):
   - Kalau hanya di existing -> DELETE (kembalikan stok)
   - Kalau hanya di new     -> CREATE (potong stok)
   - Kalau di keduanya     -> UPDATE (kembalikan stok lama, potong stok baru)
```

**Keuntungan:**
- Stok selalu konsisten.
- Serial number bisa di-manage perubahan.
- Batch bisa di-track.
- Minimal side effect.

#### 4.2 Algoritma untuk Jasa

Jasa lebih sederhana karena tidak ada stok fisik, tapi ada relasi ke `PembelianJasa`.

```
- Jasa yang dihapus -> delete record, tidak ada efek stok
- Jasa yang ditambah -> create record
- Jasa yang diubah -> update record (harga, qty)
```

**Tapi perlu validasi:** `PembelianJasa` yang sudah terpakai tidak bisa dipakai 2x (cek `whereDoesntHave('penjualanJasa')`).

#### 4.3 Recalculate & Validasi Pembayaran

Setelah items di-reconcile:
```
1. $record->recalculateTotals();
2. $record->recalculatePaymentStatus();
3. $totalPaid = $record->pembayaran()->sum('jumlah');
4. if ($totalPaid > $record->grand_total):
     -> Warning: "Total pembayaran melebihi grand total. Periksa kembali."
   if ($totalPaid > 0 && $totalPaid < $record->grand_total):
     -> Info: "Masih ada kekurangan pembayaran."
```

**Saran UX:**
- Tampilkan banner warning di atas form kalau pembayaran tidak sesuai.
- Atau tampilkan modal konfirmasi sebelum save.

#### 4.4 Audit Trail

Setiap perubahan item harus di-log:
```php
ValidationLog::log([
    'source_type' => 'Penjualan',
    'source_action' => 'edit_items',
    'field_name' => 'items_temp',
    'input_data' => [
        'added' => [...],
        'removed' => [...],
        'updated' => [...],
    ],
    'severity' => 'info',
]);
```

---

## 5. Batasan yang Direkomendasikan

Meski dibuka, tetap ada batasan untuk keamanan:

| Batasan | Alasan |
|---------|--------|
| **Hanya Draft & Unlocked** | `canEditItems()` tetap cek `isDraft() && !is_locked` |
| **Tidak bisa ganti Produk jika sudah ada RMA** | Cek `Rma::hasActiveRmaForBatch()` sebelum update |
| **Tidak bisa edit kalau bagian dari Tukar Tambah** | Gunakan `canDelete()` logic, atau minimal warning |
| **Tidak bisa ganti Batch ke batch tanpa stok** | Validasi stok sama seperti create |
| **Qty tidak boleh < jumlah yang sudah terjual/retur** | Kalau sudah ada retur dari item ini, qty minimal = retur qty |

---

## 6. Implementation Plan

### Phase 1: Refactor `canEditItems()` (Minimal Change)
**File:** `app/Models/Penjualan.php`

Ubah:
```php
public function canEditItems(): bool
{
    return $this->isDraft()
        && ! $this->is_locked;
        // HAPUS: && ! $this->items()->exists()
        // HAPUS: && ! $this->jasaItems()->exists();
}
```

**Risiko:** Tanpa update `EditPenjualan`, items tidak akan di-save. Ini aman (tidak merusak data), tapi belum solve masalah user.

### Phase 2: Implement Diff Engine di `EditPenjualan`
**File:** `app/Filament/Resources/PenjualanResource/Pages/EditPenjualan.php`

Tambah method:
- `reconcileItems(array $newItems): void`
- `calculateItemDiff(array $existing, array $new): array`
- `validateEditItems(array $items): void` (tambahan validasi batch, RMA, TT)

Modifikasi `handleRecordUpdate`:
```php
if ($record->canEditItems()) {
    $this->reconcileItems($this->itemsToCreate);
}
```

### Phase 3: Update Form UI
**File:** `app/Filament/Resources/PenjualanResource.php`

- Pastikan `addable`, `deletable`, `disabled` sudah benar.
- Tambah warning banner kalau pembayaran tidak sesuai.

### Phase 4: Penanganan Jasa
**File:** `app/Filament/Resources/PenjualanResource/Pages/EditPenjualan.php`

- Implementasi serupa untuk `jasaItems` (simpler, no stock).
- Validasi referensi `pembelian_jasa_id` tetap valid.

### Phase 5: Testing Scenarios
1. **Tambah item baru** -> Stok berkurang, total naik.
2. **Hapus item** -> Stok kembali, total turun.
3. **Ubah qty (naik)** -> Stok berkurang selisih, total naik.
4. **Ubah qty (turun)** -> Stok kembali selisih, total turun.
5. **Ganti batch** -> Stok lama kembali, stok baru berkurang.
6. **Ganti produk** -> Stok lama kembali, stok baru berkurang.
7. **Edit dengan pembayaran existing** -> Warning muncul kalau total berubah.
8. **Edit Tukar Tambah** -> Error/guard mencegah.
9. **Edit item yang sudah RMA** -> Error/guard mencegah.
10. **Concurrent edit** -> DB transaction + locking mencegah race condition.

---

## 7. Quick Win (Jika Mau Segera)

Kalau user butuh solusi cepat tanpa diff engine kompleks, bisa pakai pattern **"Delete All & Recreate"** dengan safety checks:

```php
// Di handleRecordUpdate
if ($record->canEditItems()) {
    DB::transaction(function () use ($record) {
        // 1. Kembalikan semua stok existing
        foreach ($record->items as $item) {
            // Manual stock return (bypass observer untuk existing)
            StockBatch::incrementWithLock(...);
            $item->delete();
        }
        
        // 2. Recreate items baru
        $this->createItemsWithFifo($this->itemsToCreate);
        
        // 3. Recalculate
        $record->recalculateTotals();
        $record->recalculatePaymentStatus();
    });
}
```

**Pro:** Simple, menggunakan kode yang sudah ada.
**Con:** Serial number & custom HPP yang sudah di-edit manual akan hilang (karena recreate). Tidak bisa partial edit (misal: ubah harga saja tanpa sentuh stok).

---

## 8. Decision Matrix

| Solusi | Stok Konsisten | Serial Aman | Pembayaran Aman | Complexity | Rekomendasi |
|--------|---------------|-------------|-----------------|------------|-------------|
| **Status Quo** (tetap kunci) | Ya | Ya | Ya | - | Aman tapi tidak fleksibel |
| **Quick Win** (delete all & recreate) | Ya* | Tidak | Perlu recalc | Rendah | **Bisa dipakai untuk MVP** |
| **Diff Engine** (surgical update) | Ya | Ya | Perlu recalc | Sedang | **Rekomendasi utama** |
| **Void & Recreate** | Ya | Ya | Ya | Rendah | UX kurang bagus |

*Catatan: Quick Win aman untuk stok karena kita kembalikan dulu baru recreate.

---

## 9. Kesimpulan

**Efek terburuk kalau langsung buka tanpa solusi:**
1. Stok inkonsisten (tidak dikembalikan saat hapus, double dip saat ganti produk).
2. Pembayaran tidak sesuai grand total.
3. Serial number hilang/nyangkut.
4. Data Tukar Tambah & RMA rusak.

**Solusi yang direkomendasikan:**
> Implementasi **Diff-Based Reconciliation** di `EditPenjualan` dengan tetap menjaga batasan Draft + Unlocked + Validasi Stok + Recalculate Payment.

**Langkah pertama yang bisa dikerjakan sekarang:**
1. Ganti `canEditItems()` untuk menghapus pengecekan `items()->exists()`.
2. Modifikasi `EditPenjualan::handleRecordUpdate()` dengan pattern "kembalikan stok lama + recreate baru".
3. Tambahkan recalculate totals & payment status setelah update items.
4. Testing scenario 1-5 di atas.

---

*Plan ini disusun berdasarkan analisis kode:*
- `app/Models/Penjualan.php`
- `app/Models/PenjualanItem.php`
- `app/Filament/Resources/PenjualanResource.php`
- `app/Filament/Resources/PenjualanResource/Pages/EditPenjualan.php`
