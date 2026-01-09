# Dokumentasi Pembaruan Sistem - 9 Januari 2026
## Topik: Peningkatan Modul Penjadwalan Service

Dokumen ini menjelaskan perubahan teknis dan fungsional yang dilakukan pada modul `PenjadwalanService` untuk meningkatkan alur kerja penerimaan servis, manajemen pengecekan unit (crosscheck), dan pencetakan dokumen.

---

### 1. Fitur Baru & Perubahan Fungsional

#### A. Import dari Nota Penjualan
**Fungsi:** Memudahkan pengisian data pelanggan dengan mengambil data dari transaksi penjualan sebelumnya.
- **Lokasi:** Form "Penerimaan Service" > Bagian "Informasi Pelanggan".
- **Cara Kerja:**
  - User memilih nomor nota dari dropdown (searchable).
  - Sistem otomatis mengisi Nama Member, No HP, dan Alamat berdasarkan data nota tersebut.
  - Field ini tidak disimpan ke database (`dehydrated: false`), hanya sebagai alat bantu pengisian (helper).

#### B. Sistem Crosscheck & Atribut (Kondisi Fisik, Aplikasi, Game, OS)
**Fungsi:** Mencatat detail kelengkapan dan kondisi unit dengan lebih terstruktur.
- **Struktur Baru:** Atribut dibagi menjadi 4 tab kategori:
  1. **Crosscheck**: Kondisi fisik (Lecet, Baut hilang, dll).
  2. **Aplikasi**: List software yang diminta/diinstall.
  3. **Game**: List game yang diminta.
  4. **OS**: Sistem operasi.
- **Hierarki (Parent-Child):**
  - Item memiliki hubungan Induk (Parent) dan Anak (Child).
  - Pada form input, jika **Parent** dicentang, maka daftar **Child** akan muncul.
  - Ini mengurangi kekacauan tampilan (clutter) jika item tidak relevan.

#### C. Pemisahan Tampilan Cetak (Print Views)
**Fungsi:** Memisahkan dokumen untuk customer (Invoice/Tanda Terima) dan teknisi/arsip (Checklist).
1. **Cetak Invoice (Tanda Terima):**
   - Fokus pada data pelanggan, unit, keluhan, dan estimasi biaya/waktu.
   - Layout bersih, informasi crosscheck detail disembunyikan agar tidak memenuhi kertas.
2. **Cetak Checklist:**
   - Dokumen khusus berukuran A4.
   - Menampilkan **SELURUH** detail atribut yang dicentang (Kondisi, Apps, OS, Game).
   - Digunakan sebagai panduan teknisi atau bukti fisik kondisi awal/akhir unit.

#### D. Manajemen Master Data Atribut
**Lokasi:** Menu `Transaksi` > `Penerimaan Service` > `Atribut Crosscheck`
- Halaman khusus (`AtributCrosscheck`) yang menyatukan 4 Resource dalam satu tampilan Tab.
- Memudahkan admin menambah/mengedit master data item checklist tanpa berpindah-pindah menu.

#### E. Tampilan Tabel Service
- Tombol aksi (View, Edit, Print) kini dikelompokkan dalam satu menu dropdown bertuliskan **"Menu"** agar tabel tidak terlalu lebar.

---

### 2. Implementasi Teknis (Untuk Developer)

#### A. Struktur Database
Relasi `PenjadwalanService` menggunakan `belongsToMany` ke 4 tabel baru:
- `crosschecks`
- `list_aplikasis`
- `list_games`
- `list_os`
Semua tabel ini memiliki kolom `parent_id` untuk hierarki.

#### B. Custom Saving Logic (PENTING)
Karena form input atribut menggunakan UI kustom (Checkbox + CheckboxList dinamis) yang tidak standar Filament, logika penyimpanan relasi **di-override** pada level Page.

L file: `app/Filament/Resources/Penjadwalan/PenjadwalanServiceResource/Pages/`
- **CreatePenjadwalanService.php** & **EditPenjadwalanService.php**
- Menggunakan method `mutateFormDataBeforeCreate` / `mutateFormDataBeforeSave` untuk menangkap input dari form atribut (yang bernama `attr_...`).
- Data atribut disimpan sementara di variabel `$pendingRelations` dan dihapus dari `$data` utama agar tidak error saat insert ke tabel utama.
- Menggunakan `afterCreate` / `afterSave` untuk menyimpan relasi (`sync`) ke pivot table setelah record utama berhasil dibuat/diupdate.

#### C. Layout Cetak (Blade Views)
- **Invoice:** `resources/views/filament/resources/penjadwalan-service/print.blade.php`
- **Checklist:** `resources/views/filament/resources/penjadwalan-service/print-crosscheck.blade.php`
- Style menggunakan CSS native di dalam file blade untuk konsistensi cetak (A4 layout).

---

### 3. Cara Mengubah/Menambah Fitur

**1. Menambah Item Checklist Baru:**
- Buka menu **Atribut Crosscheck**.
- Pilih Tab (Misal: Aplikasi).
- Klik "Create".
- Jika ini kategori utama, kosongkan Parent. Jika sub-item, pilih Parent-nya.

**2. Mengubah Layout Print:**
- Edit file `.blade.php` yang relevan di folder resources.
- Gunakan class utility yang sudah ada atau tambahkan CSS inline pada bagian `<style>`.

**3. Mengubah Logika Form:**
- Edit `PenjadwalanServiceResource.php`.
- Perhatikan method `getAttributeSchema` yang bertugas me-render checkbox dinamis.

---
*Dibuat otomatis oleh AI Assistant - 9 Januari 2026*
