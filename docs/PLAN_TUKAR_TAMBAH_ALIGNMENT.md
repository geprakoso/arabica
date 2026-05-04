# Plan Komprehensif: Penyelarasan Tukar Tambah dengan Penjualan & Pembelian

> **Versi**: 1.0  
> **Tanggal**: 2026-05-04  
> **Status**: Draft — Menunggu Approval  
> **Author**: AI Assistant

---

## 1. Ringkasan Eksekutif

Modul **Tukar Tambah (TT)** saat ini berfungsi sebagai transaksi gabungan (Penjualan + Pembelian dalam 1 nota), tetapi memiliki **aturan bisnis yang tidak konsisten** dengan modul Penjualan dan Pembelian standar. Ketidakkonsistenan ini mencakup model stok, status dokumen, validasi item, dan perilaku edit/hapus.

Plan ini bertujuan untuk **menyelaraskan seluruh aturan bisnis TT** agar sejalan dengan kebijakan yang berlaku di modul Penjualan (`DRAFT → FINAL → LOCKED → VOID`) dan Pembelian (`Draft → Final/Locked`).

---

## 2. Tujuan

1. **State Machine**: TT menggunakan status dokumen `DRAFT / FINAL / LOCKED / VOID` (sama seperti Penjualan).
2. **Lock Pembelian**: Bagian Pembelian di TT **terkunci total saat edit** (sama seperti Pembelian standar).
3. **Model Stok**: TT menggunakan `StockBatch` + pengecekan **RMA** (sama seperti Penjualan standar).
4. **Status Bayar**: Seragamkan ke `LUNAS / TEMPO` saja (hilangkan status `DP` atau tambahkan ke semua modul).
5. **Validasi Serial Number**: Hitung hanya SN yang **sudah terisi** (sama seperti Penjualan).
6. **Validasi Duplikat**: Konsistenkan aturan duplikat produk+batch+kondisi.
7. **Audit Trail**: Semua perubahan stok tercatat di `StockMutation`.

---

## 3. Temuan & Perbandingan Saat Ini

| # | Aspek | Penjualan Standar | Pembelian Standar | Tukar Tambah (Sekarang) | Target TT |
|---|-------|-------------------|-------------------|------------------------|-----------|
| 1 | **Status Dokumen** | `draft → final → locked` + void | `draft → final` (is_locked) | Tidak ada status | **Draft → Final → Locked + Void** |
| 2 | **Model Stok** | `StockBatch` + RMA check | — | `PembelianItem.qtySisa` langsung | **StockBatch + RMA check** |
| 3 | **Edit Item** | Hanya Draft | **Total locked** saat edit | Selalu bisa edit | **Penjualan: hanya Draft; Pembelian: total locked** |
| 4 | **Status Bayar** | `LUNAS / TEMPO` | `LUNAS / TEMPO` | `LUNAS / DP / TEMPO` | **LUNAS / TEMPO** (rekomendasi) |
| 5 | **Serial Number** | Hitung yang terisi | — | Hitung total entri | **Hitung yang terisi** |
| 6 | **Duplikat Produk** | Tidak diizinkan (implisit) | Diizinkan beda kondisi | Diblokir eksplisit | **Seragamkan dengan Penjualan** |
| 7 | **Grand Total** | `max(0, total - diskon)` | `qty * hpp` | `Penjualan - Pembelian` (bisa negatif) | **Pertahankan bisa negatif** (karena sifat TT) |
| 8 | **Jasa Ref** | Ada `pembelian_jasa_id` | — | Tidak ada | **Optional: tambahkan `pembelian_jasa_id`** |
| 9 | **Supplier** | — | Pilih manual | Auto "User Jual" | **Pertahankan auto "User Jual"** |
| 10 | **Audit Stok** | `StockMutation` | — | Tidak ada | **Gunakan `StockMutation`** |

---

## 4. Rencana Perubahan Detail

### FASE 1: State Machine & Status Dokumen (Prioritas: 🔴 TINGGI)

#### 4.1.1 Migration Database
- **File baru**: `2026_05_04_xxxxxx_add_status_dokumen_to_tukar_tambah.php`
- **Field baru** di `tb_tukar_tambah`:
  - `status_dokumen` (enum: `draft`, `final`, `locked`, `voided`) → default `draft`
  - `is_locked` (boolean) → default `false`
  - `posted_at` (datetime, nullable)
  - `posted_by_id` (foreignId ke users, nullable)
  - `voided_at` (datetime, nullable)
  - `voided_by_id` (foreignId ke users, nullable)
  - `void_used` (boolean) → default `false`

#### 4.1.2 Model `TukarTambah.php`
Tambahkan state machine methods (mirroring `Penjualan.php`):

```php
public function isDraft(): bool
public function isFinal(): bool
public function isLocked(): bool
public function isVoided(): bool
public function canPost(): bool
public function canVoid(): bool
public function canLock(): bool
public function canEditItems(): bool
public function canEditJasa(): bool
public function canEditPayment(): bool
public function post(): void
public function voidToDraft(): void
public function lockFinal(): void
```

**Aturan State TT**:
- `DRAFT`: Semua field bisa diedit (kecuali bagian Pembelian yang sudah locked).
- `FINAL`: Item & Jasa terkunci. Hanya pembayaran yang bisa ditambah/diubah (sama seperti Penjualan standar setelah void).
- `LOCKED`: Tidak ada yang bisa diubah sama sekali.
- `VOID`: Dari Final ke Draft (1x). Item & Jasa tetap terkunci, hanya pembayaran yang bisa diubah.

#### 4.1.3 Model `Penjualan.php` (yang terkait TT)
- Saat TT di-create, set `penjualan.status_dokumen = 'draft'`.
- Saat TT di-post, update `penjualan.status_dokumen = 'final'`.
- Saat TT di-void, update `penjualan.status_dokumen = 'draft'` + `void_used = true`.
- Saat TT di-lock, update `penjualan.is_locked = true`.

#### 4.1.4 Model `Pembelian.php` (yang terkait TT)
- Saat TT di-create, set `pembelian.is_locked = false`.
- Saat TT di-post, set `pembelian.is_locked = true` (lock permanen).
- Saat TT di-void, `pembelian.is_locked` tetap `true` (tidak bisa diubah).

---

### FASE 2: Lock Pembelian saat Edit (Prioritas: 🔴 TINGGI)

#### 4.2.1 Form TT (`TukarTambahResource.php`)
Bagian **Pembelian (Barang Masuk)** harus **total locked** saat edit, mengikuti kebijakan Pembelian standar:

```php
// Di Tab Pembelian
TableRepeater::make('items')
    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\TukarTambahResource\Pages\EditTukarTambah)
    ->deletable(fn($livewire) => !($livewire instanceof EditTukarTambah))
    ->reorderable(fn($livewire) => !($livewire instanceof EditTukarTambah))
    ->addable(fn($livewire) => !($livewire instanceof EditTukarTambah))
    ->cloneable(false)
```

Semua field item pembelian (`id_produk`, `kondisi`, `qty`, `hpp`, `harga_jual`) juga di-set `disabled` saat edit.

#### 4.2.2 Header Pembelian
Field header pembelian (`id_supplier`, `tipe_pembelian`, `no_po`) juga **disabled** saat edit.

#### 4.2.3 Pengecualian
Jika TT masih `DRAFT` dan belum pernah di-post, bagian Pembelian **bisa** diedit. Tapi begitu di-post (status menjadi `FINAL`), Pembelian terkunci permanen.

> **Kebijakan**: Pembelian di TT mengikuti aturan Pembelian standar — **setelah create, tidak bisa diubah**.

---

### FASE 3: Model Stok & RMA (Prioritas: 🔴 TINGGI)

#### 4.3.1 Ganti Model Stok TT ke `StockBatch`
Saat ini TT menggunakan `PembelianItem::qtySisaColumn()` untuk cek stok. Ini harus diganti dengan `StockBatch`.

**Perubahan di `TukarTambahResource.php`**:
```php
// SEBELUM (sekarang):
$available = \App\Filament\Resources\PenjualanResource::getAvailableQty($productId, $condition);

// SESUDAH (sama seperti Penjualan):
$available = \App\Models\StockBatch::getAvailableQty($productId, $condition, $batchId);
```

**Perubahan di `CreateTukarTambah.php` & `EditTukarTambah.php`**:
- Validasi stok menggunakan `StockBatch::where('qty_available', '>', 0)`.
- Fulfillment menggunakan `StockBatch::decrementWithLock()` (dengan audit trail `StockMutation`).
- Tambahkan pengecekan RMA aktif sebelum memilih produk.

#### 4.3.2 RMA Check
Tambahkan filter di dropdown produk TT:
```php
// Hanya tampilkan produk yang tidak sedang dalam proses RMA aktif
Produk::query()
    ->whereHas('stockBatches', fn($q) => $q
        ->where('qty_available', '>', 0)
        ->whereHas('pembelianItem', fn($q2) => $q2
            ->whereDoesntHave('rmas', fn($rmaQuery) => $rmaQuery
                ->whereIn('status_garansi', [Rma::STATUS_DI_PACKING, Rma::STATUS_PROSES_KLAIM])
            )
        )
    )
```

#### 4.3.3 Audit Trail Stok
Setiap pengurangan stok di TT harus mencatat `StockMutation`:
```php
StockBatch::decrementWithLock($batchId, $qty, [
    'type' => 'sale',
    'reference_type' => 'Penjualan',
    'reference_id' => $penjualan->id_penjualan,
    'notes' => 'Tukar Tambah: ' . $tukarTambah->no_nota,
]);
```

---

### FASE 4: Status Pembayaran (Prioritas: 🟡 MEDIUM)

#### 4.4.1 Seragamkan ke 2 Status
Hapus status `DP` dari TT. Gunakan hanya `LUNAS` dan `TEMPO`.

**Perubahan**:
- Di `TukarTambahResource::calculatePaymentStatus()`:
  ```php
  // SEBELUM:
  if ($paidPenjualan >= $totalPenjualan && $paidPembelian >= $totalPembelian) return 'LUNAS';
  if ($paidPenjualan == 0 && $paidPembelian == 0) return 'TEMPO';
  return 'DP';

  // SESUDAH:
  $grandTotal = $totalPenjualan - $totalPembelian;
  $totalPaid = $paidPenjualan - $paidPembelian; // Net payment
  return $totalPaid >= $grandTotal ? 'LUNAS' : 'TEMPO';
  ```

> **Catatan**: Jika kebutuhan bisnis memang memerlukan status `DP`, maka tambahkan `DP` ke Penjualan dan Pembelian standar juga.

---

### FASE 5: Validasi Serial Number (Prioritas: 🟡 MEDIUM)

#### 4.5.1 Hitung SN yang Terisi
Ubah cara hitung serial number di TT agar sama dengan Penjualan standar — hanya menghitung SN yang **sudah diisi** (tidak kosong).

**Perubahan di form TT**:
```php
TextInput::make('serials_count')
    ->formatStateUsing(fn (Get $get): string => 
        count(array_filter($get('serials') ?? [], fn($s) => !empty($s['sn']))) . ' serials'
    )
```

---

### FASE 6: Validasi Duplikat Produk (Prioritas: 🟡 MEDIUM)

#### 4.6.1 Seragamkan Aturan
- **Penjualan standar**: Tidak ada validasi backend duplikat eksplisit di Resource (validasi mungkin ada di Page Create).
- **TT saat ini**: Memblokir duplikat `produk + batch + kondisi` secara eksplisit.
- **Rekomendasi**: Pertahankan validasi duplikat TT (karena lebih ketat dan aman), tapi pertimbangkan untuk menambahkan validasi serupa ke Penjualan standar agar konsisten.

---

### FASE 7: Perilaku Edit & Hapus (Prioritas: 🟡 MEDIUM)

#### 4.7.1 Edit TT
- **DRAFT**: Bisa edit semua (kecuali Pembelian sudah locked).
- **FINAL**: Hanya bisa edit pembayaran (tambah/hapus pembayaran). Item & Jasa terkunci.
- **LOCKED**: Tidak bisa edit apa pun.
- **VOID**: Sama seperti FINAL — hanya pembayaran yang bisa diubah.

#### 4.7.2 Hapus TT
- Hanya bisa dihapus jika status `DRAFT`.
- Jika sudah `FINAL`, `LOCKED`, atau `VOID` — **tidak bisa dihapus** (sama seperti Penjualan standar).
- Jika item pembelian sudah dipakai transaksi lain — tetap diblokir.

---

### FASE 8: UI/UX & Badge (Prioritas: 🟢 RENDAH)

#### 4.8.1 Badge Status di Tabel TT
Tambahkan kolom status dokumen di tabel TT:
```php
TextColumn::make('status_dokumen')
    ->badge()
    ->color(fn(string $state): string => match($state) {
        'draft' => 'gray',
        'final' => 'success',
        'locked' => 'danger',
        'voided' => 'warning',
    })
```

#### 4.8.2 Actions di Tabel TT
Tambahkan action buttons (mirroring Penjualan):
```php
// POST: Draft -> Final
Action::make('post')
    ->visible(fn(TukarTambah $record) => $record->canPost())
    ->requiresConfirmation()
    ->action(fn(TukarTambah $record) => $record->post())

// VOID: Final -> Draft (1x)
Action::make('void')
    ->visible(fn(TukarTambah $record) => $record->canVoid())
    ->requiresConfirmation()
    ->action(fn(TukarTambah $record) => $record->voidToDraft())

// LOCK: Final -> Locked
Action::make('lock')
    ->visible(fn(TukarTambah $record) => $record->canLock())
    ->requiresConfirmation()
    ->action(fn(TukarTambah $record) => $record->lockFinal())
```

---

## 5. Timeline Implementasi

| Fase | Durasi | Deliverable |
|------|--------|-------------|
| **Fase 1** | 2 hari | Migration + Model State Machine |
| **Fase 2** | 1 hari | Lock Pembelian di Form TT |
| **Fase 3** | 3 hari | Refactor Stok ke StockBatch + RMA + StockMutation |
| **Fase 4** | 0.5 hari | Seragamkan Status Bayar |
| **Fase 5** | 0.5 hari | Fix Serial Number Count |
| **Fase 6** | 0.5 hari | Validasi Duplikat |
| **Fase 7** | 1 hari | Edit/Delete Behavior + Guards |
| **Fase 8** | 1 hari | UI Badge + Action Buttons |
| **Testing** | 2 hari | Manual Testing + Bug Fix |
| **Total** | **~11 hari** | — |

---

## 6. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| **Data stok tidak sinkron** saat migrasi dari `PembelianItem.qtySisa` ke `StockBatch` | Tinggi | Jalankan `php artisan db:seed --class=StockBatchSyncSeeder` sebelum deploy. Backup database terlebih dahulu. |
| **User bingung** karena Pembelian TT tidak bisa diedit setelah dibuat | Medium | Tambahkan tooltip/info di form: "Item pembelian tidak bisa diubah setelah disimpan." |
| **Void TT mengembalikan stok?** | Medium | **Tidak mengembalikan stok** (sama seperti Penjualan standar). Item tetap terkunci. |
| **Transaksi TT yang sudah ada** di database | Tinggi | Buat migration + command untuk set `status_dokumen = 'final'` dan `is_locked = true` untuk semua TT yang sudah ada. |
| **RMA check menyebabkan produk tidak muncul** di TT padahal stok ada | Rendah | Produk dengan RMA aktif memang **seharusnya tidak dijual** (sama seperti Penjualan standar). |

---

## 7. Checklist Implementasi

### Migration & Database
- [ ] Migration tambah `status_dokumen`, `is_locked`, `posted_at`, `posted_by_id`, `voided_at`, `voided_by_id`, `void_used` ke `tb_tukar_tambah`
- [ ] Migration set default value untuk TT yang sudah ada (`status_dokumen = 'final'`, `is_locked = true`)
- [ ] Migration tambah `status_dokumen` ke `tb_penjualan` untuk TT (jika belum ada — cek dulu)

### Model
- [ ] Update `TukarTambah.php` — tambahkan state machine methods
- [ ] Update `Penjualan.php` — sinkronkan status saat TT berubah state
- [ ] Update `Pembelian.php` — sinkronkan `is_locked` saat TT berubah state

### Resource & Form
- [ ] Update `TukarTambahResource.php` — lock Pembelian saat edit
- [ ] Update `TukarTambahResource.php` — gunakan `StockBatch` untuk stok
- [ ] Update `TukarTambahResource.php` — tambahkan RMA check di produk options
- [ ] Update `TukarTambahResource.php` — fix serial number count
- [ ] Update `TukarTambahResource.php` — tambahkan badge status dokumen di tabel
- [ ] Update `TukarTambahResource.php` — tambahkan actions Post, Void, Lock

### Pages (Create / Edit / View)
- [ ] Update `CreateTukarTambah.php` — gunakan `StockBatch::decrementWithLock()`
- [ ] Update `CreateTukarTambah.php` — tambahkan `StockMutation` audit trail
- [ ] Update `EditTukarTambah.php` — validasi state machine sebelum edit
- [ ] Update `ViewTukarTambah.php` — tampilkan action Post/Void/Lock

### Testing
- [ ] Test create TT baru (draft)
- [ ] Test post TT (draft → final)
- [ ] Test lock TT (final → locked)
- [ ] Test void TT (final → draft, 1x)
- [ ] Test edit TT saat draft (penjualan bisa, pembelian locked)
- [ ] Test edit TT saat final (hanya pembayaran)
- [ ] Test hapus TT saat draft vs final
- [ ] Test stok menggunakan StockBatch
- [ ] Test RMA check (produk dengan garansi aktif tidak muncul)
- [ ] Test serial number count
- [ ] Test status bayar LUNAS/TEMPO

---

## 8. Pertanyaan untuk Stakeholder

1. **Apakah status `DP` memang tidak diperlukan di TT?** Atau sebaiknya kita tambahkan `DP` ke Penjualan & Pembelian standar juga?
2. **Apakah Pembelian di TT benar-benar harus total locked saat edit?** (Mengikuti kebijakan Pembelian standar yang sangat ketat)
3. **Bagaimana dengan TT yang sudah ada di database?** Apakah langsung dianggap `FINAL + LOCKED`?
4. **Apakah Void TT boleh dilakukan berkali-kali?** Atau hanya 1x seperti Penjualan standar?
5. **Apakah perlu menambahkan `pembelian_jasa_id` untuk tracking jasa di TT?**

---

*Plan ini akan di-update sesuai feedback dan hasil diskusi.*
