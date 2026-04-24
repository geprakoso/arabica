# Kebijakan Modul Pembelian (Purchase Module)

> **Versi:** 1.0 | 2026
> **Status:** Dokumen Resmi — Bersifat Rahasia, Untuk Internal Perusahaan

---

## 1. Sistem & Metode Pembelian

### R01 — Metode Sistem Batch

Sistem pembelian menggunakan metode **Batch**. Metode FIFO *(First In First Out)* dan LIFO *(Last In First Out)* **tidak digunakan** dalam modul ini.

### R02 — Produk Duplikat dengan Kondisi Berbeda

Pada halaman *Create Pembelian*, diperbolehkan menambahkan **2 (dua) produk dengan nama yang sama** selama memiliki kondisi berbeda, yaitu:
- `Baru`
- `Bekas`

### R03 — Kolom Item Barang

Setiap item barang dalam pembelian **wajib** memiliki kolom berikut:

| Kolom     | Keterangan                        |
|-----------|-----------------------------------|
| Produk    | Nama produk                       |
| Kondisi   | Baru / Bekas                      |
| Qty       | Jumlah barang                     |
| HPP       | Harga Pokok Pembelian             |
| Harga Jual| Harga jual yang ditetapkan        |
| Subtotal  | Dihitung otomatis: `Qty × HPP`    |

### R04 — Subtotal Menggantikan SN & Garansi

Kolom **SN** *(Serial Number)* dan **GARANSI** dihapus dari form pembelian. Keduanya digantikan oleh kolom **Subtotal** yang dihitung otomatis:

```
Subtotal = Qty × HPP
```

### R05 — Pembelian Item Jasa Tanpa Item Produk

- Pembuatan pembelian boleh **hanya berisi item jasa saja** tanpa item produk, dan sebaliknya.
- Sistem **tidak boleh** memvalidasi / mewajibkan item jasa harus disertai item produk, atau sebaliknya.

---

## 2. Status Pembayaran

### R06 — Hanya 2 Status Pembayaran

Status pembayaran pada modul pembelian **hanya terdiri dari 2 pilihan**:

| Status  | Kondisi                        | Keterangan                                                        |
|---------|--------------------------------|-------------------------------------------------------------------|
| `TEMPO` | Pembayaran < Grand Total       | Pembayaran belum lunas. Sistem mencatat kekurangan pembayaran.    |
| `LUNAS` | Pembayaran ≥ Grand Total       | Pembayaran penuh atau lebih. Kelebihan ditampilkan di view detail. |

> Tidak ada status lain di luar kedua nilai tersebut.

### R07 — Update Status Otomatis

Status pembayaran **diperbarui secara otomatis** oleh sistem berdasarkan total pembayaran yang diinput:
- Jika pembayaran **≥ grand total** → status menjadi `LUNAS`
- Jika pembayaran **< grand total** → status tetap `TEMPO`

### R08 — Kelebihan Pembayaran

- Jika jumlah pembayaran **melebihi grand total**, kelebihan tersebut **wajib ditampilkan** di halaman view detail pembelian.
- Sistem harus memastikan **tidak ada error validasi** saat input pembayaran yang melebihi grand total.

---

## 3. Aturan Edit Pembelian

### R09 — Lock Section Item Barang saat Edit

Saat melakukan **Edit Pembelian**, section item barang akan:
- **Dikunci** *(locked)*
- **Dinonaktifkan** *(disabled)*

Pengguna **tidak dapat mengubah** qty, produk, atau detail item lainnya untuk mencegah kerusakan dan inkonsistensi data stok.

### R10 — Edit Terbatas: Hanya Jumlah Pembayaran

Fungsi edit pembelian **hanya diperuntukkan** untuk mengubah jumlah pembayaran. Hal ini sesuai dengan logika status `TEMPO` dan `LUNAS` yang bergantung pada nilai pembayaran.

### R11 — Simpan Grand Total di Database

Grand total **wajib disimpan** di database untuk:
- Menghindari inkonsistensi data saat ada perubahan harga.
- Menjaga performa sistem saat menampilkan daftar data yang banyak.

---

## 4. Validasi & Hapus Data

### R12 — Cegah Hapus jika Ada Transaksi Penjualan

Sistem **wajib mencegah penghapusan** data pembelian apabila produk dari pembelian tersebut sudah digunakan dalam transaksi penjualan (stok sudah masuk dan terpakai).

### R13 — Larangan Hapus NO PO dengan NO TT

Data pembelian dengan **NO PO** yang sudah memiliki **NO TT** *(Tanda Terima)* **tidak boleh dihapus**. Validasi delete untuk kasus ini hanya dapat dilakukan melalui mekanisme **tukar tambah**.

### R14 — Konsistensi View Qty Pembelian

Qty yang tercatat di view pembelian **tidak boleh berkurang** meskipun produk sudah digunakan di transaksi penjualan. Angka harus konsisten dari pertama kali pembelian diinput, karena sistem mengelola stok melalui mekanisme **stok masuk** dan **stok keluar** yang terpisah.

### R15 — Kelola File Bukti Transfer

File bukti transfer yang diupload **wajib dikelola dengan benar** untuk mencegah *file orphan* (file yang tidak memiliki referensi di database) ketika data pembayaran dihapus.

---

## 5. Finalisasi & Lock Pembelian

### R16 — Tombol Lock Final di View Pembelian

Pada halaman view pembelian final, tersedia **satu tombol khusus** yang berfungsi untuk mengunci seluruh data pembelian secara permanen.

> ⚠️ **PERHATIAN PENTING**
>
> Tombol *Lock Final* bersifat **irreversible**. Setelah diaktifkan, sistem **tidak menyediakan mekanisme** untuk membatalkan atau membuka kembali kunci tersebut.
> **Pastikan seluruh data sudah benar sebelum mengklik tombol ini.**

---

## 6. Keamanan & Konkurensi Data

### R17 — Pessimistic Locking pada Stok Batch

Sistem **wajib** mengimplementasikan **pessimistic locking** *(penguncian data pada level database)* untuk mencegah *race condition* saat multiple transaksi penjualan mengakses stok batch yang sama secara bersamaan.

#### Skenario Race Condition yang Dicegah:
Ketika **2 (dua) atau lebih** kasir melakukan transaksi penjualan produk yang sama dari batch stok yang sama pada waktu bersamaan:
- Tanpa locking: Kedua kasir membaca jumlah stok yang sama, mengakibatkan **oversell** (penjualan melebihi stok yang tersedia)
- Dengan locking: Kasir kedua menunggu kasir pertama selesai, membaca stok terbaru, dan dicek ketersediaannya

#### Implementasi Wajib:
```
1. SET LOCK pada record batch stok
2. READ jumlah stok tersedia
3. VALIDASI: Stok cukup atau tidak
4. WRITE pengurangan stok (jika validasi lolos)
5. RELEASE LOCK
```

> 🔒 **Catatan Keamanan**
>
> Semua operasi pengurangan stok batch **harus** menggunakan mekanisme database transaction dengan `lockForUpdate()` (atau ekuivalen) untuk memastikan integritas data dan mencegah oversell.

---

## Ringkasan Aturan

| Kode  | Judul                                    | Kategori              |
|-------|------------------------------------------|-----------------------|
| R01   | Metode Sistem Batch                      | Sistem & Metode       |
| R02   | Produk Duplikat dengan Kondisi Berbeda   | Sistem & Metode       |
| R03   | Kolom Item Barang                        | Sistem & Metode       |
| R04   | Subtotal Menggantikan SN & Garansi       | Sistem & Metode       |
| R05   | Pembelian Item Jasa Tanpa Item Produk    | Sistem & Metode       |
| R06   | Hanya 2 Status Pembayaran               | Status Pembayaran     |
| R07   | Update Status Otomatis                   | Status Pembayaran     |
| R08   | Kelebihan Pembayaran                     | Status Pembayaran     |
| R09   | Lock Section Item Barang saat Edit       | Aturan Edit           |
| R10   | Edit Terbatas: Hanya Jumlah Pembayaran   | Aturan Edit           |
| R11   | Simpan Grand Total di Database           | Aturan Edit           |
| R12   | Cegah Hapus jika Ada Transaksi Penjualan | Validasi & Hapus      |
| R13   | Larangan Hapus NO PO dengan NO TT        | Validasi & Hapus      |
| R14   | Konsistensi View Qty Pembelian           | Validasi & Hapus      |
| R15   | Kelola File Bukti Transfer               | Validasi & Hapus      |
| R16   | Tombol Lock Final di View Pembelian      | Finalisasi & Lock     |
| R17   | Pessimistic Locking pada Stok Batch      | Keamanan & Konkurensi |
