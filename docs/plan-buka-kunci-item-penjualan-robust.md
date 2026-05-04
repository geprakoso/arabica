# Plan: Buka Kunci Item Penjualan — Versi Robust (In-Place Edit)

> Status: **PLAN** — belum dieksekusi.
> Tujuan: Section Produk & Jasa di edit draft penjualan bisa diedit kapan saja (selama draft & belum locked) tanpa harus hapus-semua-lalu-buat-ulang.

---

## 1. Ringkasan Masalah (Current vs Target)

### Current (Quick Win)
| Aspek | Perilaku |
|-------|----------|
| `canEditItems()` | `isDraft() && !is_locked` |
| Edit item produk | Hapus semua item lama → buat ulang semua |
| ID item produk | Berubah setiap edit |
| Serial Number | Harus diisi ulang setiap edit |
| Audit trail item | ID tidak stabil, history bercabang |

### Target (Robust)
| Aspek | Perilaku |
|-------|----------|
| `canEditItems()` | Tetap `isDraft() && !is_locked` |
| Edit item produk | **Update yang ada, hapus yang di-remove, tambah yang baru** |
| ID item produk | **Stabil** (item yang sama tetap ID-nya) |
| Serial Number | **Tetap terisi** setelah edit |
| Audit trail item | Lebih bersih, stok mutation lebih presisi |

---

## 2. File yang Akan Diubah

| No | File | Perubahan |
|----|------|-----------|
| 1 | `app/Filament/Resources/PenjualanResource/Pages/EditPenjualan.php` | Logika `handleRecordUpdate` di-refactor dari "hapus semua + recreate" jadi "sync items" (upsert/hapus) |
| 2 | `app/Models/PenjualanItem.php` | Tambah method helper `syncFromFormData()` atau perbaiki observer agar handle update in-place dengan benar |
| 3 | `tests/Unit/SalesModule/StateMachineTest.php` | Tambah test: edit draft yang sudah punya item tidak menghapus ID lama, stok tetap konsisten, SN tetap ada |
| 4 | `app/Filament/Resources/PenjualanResource.php` | (Opsional) Tambah `->key('id_penjualan_item')` atau hidden ID agar TableRepeater tahu item mana yang update vs baru |

---

## 3. Rencana Implementasi Detail

### 3.1. Tambah Hidden ID di Form Repeater (File 4)

Di bagian `TableRepeater::make('items_temp')`, tambahkan hidden field agar saat edit, item yang sudah ada punya ID-nya:

```php
Hidden::make('id_penjualan_item')
    ->default(null)
    ->dehydrated(true),
```

Saat `mutateFormDataBeforeFill`, mapping item existing harus include `id_penjualan_item`:

```php
$data['items_temp'] = collect($this->record->items)
    ->map(fn ($item) => [
        'id_penjualan_item' => $item->id_penjualan_item, // <-- tambah ini
        'id_produk' => $item->id_produk,
        ...
    ])
    ->all();
```

> **Catatan:** Filament TableRepeater tidak selalu support `->key()` untuk track row. Alternatif: simpan ID di hidden field dan saat save, bandingkan array ID yang ada di DB vs yang dikirim form.

---

### 3.2. Refactor `handleRecordUpdate` (File 1)

Ubah dari:
```php
if ($record->canEditItems()) {
    $record->items()->delete(); // hapus semua
    if (!empty($this->itemsToCreate)) {
        $this->validateBeforeSave($this->itemsToCreate);
        $this->createItemsWithFifo($this->itemsToCreate);
    }
}
```

Menjadi logika **Sync**:

```php
if ($record->canEditItems()) {
    $incomingItems = $this->itemsToCreate ?? [];
    $existingItems = $record->items()->get()->keyBy('id_penjualan_item');

    $incomingIds = collect($incomingItems)
        ->pluck('id_penjualan_item')
        ->filter()
        ->values()
        ->all();

    // 1. Hapus item yang tidak ada di form (user hapus row)
    $toDelete = $existingItems->keys()->diff($incomingIds);
    foreach ($toDelete as $deleteId) {
        $existingItems[$deleteId]->delete(); // stok kembali via observer
    }

    // 2. Update item yang sudah ada (jika ada perubahan)
    foreach ($incomingItems as $itemData) {
        $itemId = $itemData['id_penjualan_item'] ?? null;

        if ($itemId && $existingItems->has($itemId)) {
            // UPDATE existing
            $existingItem = $existingItems[$itemId];
            $existingItem->update([
                'id_produk' => $itemData['id_produk'],
                'id_pembelian_item' => $itemData['id_pembelian_item'],
                'qty' => $itemData['qty'],
                'harga_jual' => $itemData['harga_jual'],
                'kondisi' => $itemData['kondisi'],
                'serials' => $itemData['serials'] ?? null,
            ]);
            // Stok adjustment otomatis via observer PenjualanItem::updated
        } else {
            // CREATE new
            PenjualanItem::create([
                'id_penjualan' => $record->id_penjualan,
                ...$itemData,
            ]);
            // Stok deduction otomatis via observer PenjualanItem::created
        }
    }
}
```

---

### 3.3. Pastikan Observer `PenjualanItem` Handle Update dengan Benar (File 2)

Saat ini observer `PenjualanItem::updated` sudah ada logika "kembalikan stok lama, kurangi stok baru". Tapi perlu dicek apakah logika ini sudah handle semua edge case saat **batch berubah** atau **qty berubah**.

Periksa kembali:
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
            self::applyStockMutation($originalItem, $originalQty, 'sale_return');
        }

        // Kurangi stok baru
        self::applyStockMutation($item, -1 * $newQty, 'sale');
        ...
    });
});
```

Pastikan:
- Jika batch tidak berubah tapi qty berubah, stok adjustment benar.
- Jika batch berubah, stok lama dikembalikan ke batch lama dan stok baru dipotong dari batch baru.
- Jika tidak ada masalah, **tidak perlu ubah**.

---

### 3.4. Validasi Stok untuk Update In-Place

Validasi stok di `validateBeforeSave` saat ini hanya cek "apakah stok cukup untuk qty yang diminta". Tapi saat update in-place, kita harus mempertimbangkan:

- **Item yang di-update**: stok lama sudah terpotong, jadi qty baru boleh lebih besar asalkan tidak melebihi `available + original_qty`.
- **Item baru**: validasi seperti biasa.

Solusi:
- Pindahkan validasi stok ke dalam `handleRecordUpdate`, tepatnya **setelah** menghapus item yang di-remove dan **sebelum** update/create item.
- Atau, modifikasi `validateBeforeSave` agar menerima parameter `$existingItems` sehingga bisa menghitung `available + existing_qty`.

---

### 3.5. Penanganan Jasa (File 1)

Jasa di `PenjualanResource` menggunakan `->relationship('jasaItems')` di `TableRepeater`. Filament sudah auto-handle create/update/delete jasa. **Tidak perlu ubah**.

Tapi perlu verifikasi:
- Apakah saat `handleRecordUpdate` di `EditPenjualan`, data jasa tetap tersimpan? (Harusnya iya karena Filament handle relationship save secara default sebelum `handleRecordUpdate`).
- Jika tidak, mungkin perlu panggil `parent::handleRecordUpdate` atau sync manual.

---

## 4. Flow Data Saat Edit (Robust)

```
User klik Edit Draft
  │
  ▼
mutateFormDataBeforeFill
  │→ Mapping item existing ke items_temp (include id_penjualan_item)
  │→ Mapping jasaItems ke form
  │
User edit form (ubah qty, hapus row, tambah row, SN tetap terisi)
  │
User klik Simpan
  │
  ▼
mutateFormDataBeforeSave
  │→ Extract items_temp & jasaItems
  │→ (Tidak validasi stok di sini — pindah ke handleRecordUpdate)
  │
  ▼
handleRecordUpdate (dalam transaction)
  │
  ├── Update header penjualan (karyawan, member, tanggal, diskon, catatan)
  │
  ├── Sync Items (jika canEditItems)
  │   ├── 1. Hapus item yang tidak ada di form (ID tidak ada di incoming)
  │   │      → Stok kembali via observer
  │   ├── 2. Loop incoming items
  3.   │   ├── Jika punya ID existing → UPDATE
  3.   │   │      → Stok adjust otomatis via observer (kembali lama, potong baru)
  3.   │   └── Jika tidak punya ID → CREATE
  3.   │          → Stok dipotong via observer
  3.   └── 3. Validasi stok (opsional, bisa di step 2 per item)
  │
  ├── (Jasa auto-sync oleh Filament relationship handler)
  │
  ├── Recalculate totals
  └── Recalculate payment status
```

---

## 5. Risiko & Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| ID item tidak dikirim karena hidden field tidak tersimpan | Pastikan `Hidden::make('id_penjualan_item')->dehydrated(true)` |
| Stok race condition saat edit bersamaan | Gunakan `DB::transaction` + `lockForUpdate` pada `StockBatch` jika perlu |
| Observer `updated` tidak handle perubahan batch dengan benar | Test eksplisit: ganti batch, ganti qty, hapus item |
| Jasa tidak tersimpan karena override `handleRecordUpdate` | Pastikan `parent::handleRecordUpdate` dipanggil atau sync manual |
| Validasi duplikat produk masih perlu | Pertahankan validasi duplikat di `validateBeforeSave` atau pindah ke `handleRecordUpdate` |

---

## 6. Test yang Perlu Ditambah / Diubah

### Test Baru (File 3)

```php
test('edit draft produk in-place tidak menghapus ID item lama', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    $item = createPenjualanItem($penjualan, $batch, 2);
    $originalId = $item->id_penjualan_item;

    // Simulasi edit: ubah qty dari 2 jadi 3
    // (Asumsikan ada mekanisme form submit di test ini)
    // ...

    $item->refresh();
    expect($item->id_penjualan_item)->toBe($originalId);
    expect($item->qty)->toBe(3);
});

test('edit draft produk hapus row mengembalikan stok dan menghapus item', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    $item = createPenjualanItem($penjualan, $batch, 2);

    // Simulasi edit: hapus semua item, submit form kosong
    // ...

    expect($penjualan->items()->count())->toBe(0);
    expect($batch->fresh()->qty_available)->toBe(10);
});

test('edit draft produk tambah row baru memotong stok baru', function () {
    $batchA = createStockBatch(10);
    $batchB = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batchA, 2);

    // Simulasi edit: tambah item dari batchB
    // ...

    expect($batchB->fresh()->qty_available)->toBe(9); // atau sesuai qty baru
});
```

### Test yang Diubah
- Test `draft saved bisa edit items dan jasa` sudah diubah di quick win, tetap relevan.
- Test stok mutation count mungkin perlu disesuaikan karena update in-place menghasilkan mutation berbeda (tidak delete+create).

---

## 7. Estimasi Effort

| Task | Estimasi |
|------|----------|
| Tambah hidden ID + adjust form mapping | 15 menit |
| Refactor `handleRecordUpdate` jadi sync logic | 45–60 menit |
| Verifikasi & perbaiki observer jika perlu | 15–30 menit |
| Validasi stok untuk update in-place | 20–30 menit |
| Tulis test baru | 30–45 menit |
| Uji coba manual di browser | 15 menit |
| **Total** | **~2.5–3 jam** |

---

## 8. Keputusan

| Pilihan | Kapan Cocok |
|---------|-------------|
| **Quick Win (sudah jalan)** | Butuh segera, ID item tidak krusial, SN bisa diisi ulang |
| **Robust (plan ini)** | Butuh audit trail bersih, SN tidak mau hilang, edit draft sering dilakukan, menghindari create/delete berlebihan |

---

*Plan dibuat: 3 Mei 2026*
*Siap dieksekusi jika disetujui.*
