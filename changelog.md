# Catatan Perubahan (Changelog)

Semua perubahan penting pada proyek ini direkonstruksi dari riwayat git. Pembuatan versi sekarang mengikuti sistem CalVer (`YYYY.MM.DD`) selama aplikasi masih dalam tahap pra-1.0. Entri disusun secara kronologis dengan perubahan terbaru berada di paling atas.

## 2026.01.15
### Peningkatan UI/UX Tukar Tambah
- **Modal Serial Number & Garansi**:
  - Mengubah input serial number dari tampilan inline (nested table) menjadi **modal popup** untuk UI yang lebih bersih dan compact.
  - Menambahkan tombol **"Manage"** dengan ikon QR code yang menampilkan jumlah serial number (e.g., "2 serials").
  - Modal berisi `Repeater` untuk menambah, mengedit, dan menghapus serial number (`sn` dan `garansi`) secara individual.
  - Implementasi **data transfer** antara hidden field (`serials`) dan modal repeater (`serials_temp`) menggunakan `fillForm` dan `action` callbacks.
  - Menambahkan `->button()` pada `FormAction` agar tombol menampilkan icon dan label secara bersamaan (bukan hanya icon).
  - **Known Issue**: Count serial number belum update secara reactive setelah modal disimpan (ditunda untuk perbaikan masa depan).
- **Perbaikan Penjualan** (dari repository):
  - Memperbaiki logika visibility tombol aksi pada tabel penjualan.
  - Menyesuaikan kondisi hidden untuk action group berdasarkan status pembayaran dan keberadaan line items.
- **Refactoring Email Invoice**:
  - Menyesuaikan sintaks anonymous function pada `InvoicePenjualanMail.php` untuk konsistensi kode.
  - Mengurutkan ulang import statements sesuai standar PSR-12.
- **Dokumentasi**: Menambahkan dokumentasi teknis lengkap untuk implementasi modal serial number (`docs/2026-01-15_modal_serial_number_tukar_tambah.md`).

## 2026.01.14
### Perbaikan & Peningkatan Penjadwalan Tugas
- **Optimasi Upload Gambar RichEditor**:
  - Memperbaiki error `BadMethodCallException` pada upload gambar di deskripsi tugas.
  - Mengimplementasikan standarisasi upload: Resize otomatis ke **1080p**, konversi ke **WebP**, dan kompresi **80% quality**.
  - Menyimpan file secara terpusat di disk `public`.
- **Filter Canggih Penjadwalan Tugas**:
  - **Tab Status Filter**: Menambahkan tab navigasi cepat untuk memfilter tugas berdasarkan status: **Proses** (termasuk Pending), **Selesai**, **Batal**, dan **Semua**.
  - **Filter Periode**: Menambahkan filter rentang waktu (Hari Ini, Kemarin, 2 Hari Lalu, 3 Hari Lalu, Custom) di dalam menu filter tabel.
  - Memastikan integrasi ikon yang intuitif pada setiap tab filter.
- **Dokumentasi**: Update panduan teknis upload gambar RichEditor (`docs/rich-editor-image-standard.md`) dengan kode yang terverifikasi.
- **Fitur Edit Absensi (Admin)**:
  - Mengaktifkan fitur **Edit** pada tabel Absensi khusus untuk role `super_admin` dan `admin`.
  - **Modal Edit**: Menggunakan modal box (bukan halaman terpisah) untuk pengeditan cepat.
  - **Field Fleksibel**: Memungkinkan pengubahan Tanggal, Jam Masuk, Jam Keluar, dan Keterangan.
  - **Keamanan**: Menambahkan konfirmasi "Alasan Perubahan" yang wajib diisi dan proteksi `visible()` berbasis role.

## 2026.01.13
### Peningkatan UI/UX Lembur & Filter Absensi
- **Redesign UI Lembur (`LemburResource`)**:
  - Mengubah layout Form dan Infolist menjadi **Standard Enterprise Card Layout** yang lebih profesional dan rapi.
  - Mengganti gaya tombol aksi menjadi **Solid Colors** ("Buat Lembur" biru, "Selesai" hijau, "Terima" hijau, "Tolak" merah) untuk kejelasan visual.
  - Menyederhanakan tombol Edit & Delete menjadi gaya minimalis berwarna netral (gray/white).
  - Menambahkan kolom preview gambar (square) pada tabel list untuk "Bukti". 
- **Fitur Upload Bukti Lembur**:
  - Menambahkan kolom upload gambar "Bukti" dengan konversi otomatis ke format **WebP** dan *resize* (max Full HD) untuk optimasi penyimpanan.
  - Memperbaiki validasi upload agar menerima `jpeg`, `png`, dan `webp` dengan benar.
- **Logika & Workflow**:
  - Menambahkan fitur **Redirect** otomatis ke halaman *List* setelah berhasil membuat record baru (Create -> Redirect -> List).
  - Mengimplementasikan logika tombol dinamis pada header list: Tombol "Selesai Lembur" hanya muncul jika user memiliki lembur aktif hari ini.
  - Menambahkan validasi tombol Approval (Terima/Tolak) yang hanya muncul untuk Admin pada status Pending.
- **Filter Absensi**:
  - Menambahkan filter tanggal canggih pada `AbsensiResource` dengan opsi preset: Hari Ini (Default), Kemarin, 2 Hari Lalu, 3 Hari Lalu, dan Custom Range.
  - Menambahkan indikator visual (badge) pada filter aktif.
- **Peningkatan Penjadwalan Tugas**:
  - **Multi-Assignee**: Mengubah sistem penugasan dari 1 karyawan menjadi **Banyak Karyawan** sekaligus (Many-to-Many).
  - **Selector Durasi Cerdas**: Menambahkan pilihan cepat durasi (1 Hari, 2 Hari, 3 Hari) yang otomatis mengatur tanggal dan menyembunyikan input manual.
  - **Validasi Server-Side**: Memastikan logika tanggal tersimpan akurat (Today -> Today) menggunakan *mutation hooks*, mencegah bug pada input tersembunyi.
  - **Tombol Status Cepat**: Menambahkan tombol aksi **Proses**, **Selesai**, dan **Batal** pada halaman detail tugas untuk mempercepat workflow status.
  - **Sistem Komentar Native**: Menambahkan fitur diskusi interaktif pada detail tugas.
    - **Indikator Pesan Baru**: Badge notifikasi (Hijau) pada list tugas jika ada komentar yang belum dibaca.
    - **Integrasi Notifikasi**: Notifikasi in-app kepada Creator & Assignees saat ada komentar baru.
    - **Smart Navigation Badge**:
      - Indikator personal (hanya untuk tugas terkait).
      - Split Info: **New 🆕** (Belum dilihat) dan **Chat 💬** (Komentar baru).
    - **Optimasi Performa**: Eager Loading untuk mencegah N+1 Query pada indikator diskusi.
    - Terintegrasi langsung di halaman View (Infolist).
    - Keamanan akses: Hanya Creator dan Assignee yang bisa berkomentar.
    - Menggunakan teknologi Livewire untuk pengalaman pengguna yang responsif.
- **Kompatibilitas iPhone**:
  - Menambahkan dukungan format **HEIC/HEIF** pada upload bukti lembur.

## 2026.01.12
- Menambahkan **My Profile** dengan fitur upload avatar yang tersinkronisasi.
- Perbaikan **Upload Avatar**: Memindahkan penyimpanan ke disk `public` untuk mengatasi error 403 Forbidden.
- Refactoring **User Model**: Menggunakan observer untuk sinkronisasi otomatis avatar antara tabel `users` dan `karyawan`.
- Migrasi Database: Menambahkan kolom `avatar_url` pada tabel `users`.
- Konfigurasi Plugin: Memaksa `edit-profile` plugin menggunakan disk `public`.
- Dokumentasi teknis perbaikan tersedia di `/docs/perbaikan_sinkronisasi_avatar.md`.

## 2026.01.11
### Integrasi Gudang & Absensi Berbasis Lokasi
- **Manajemen Lokasi Gudang (`GudangResource`)**:
  - Mengimplementasikan **Interactive Map Picker** (Leaflet/OSM) untuk pemilihan lokasi visual.
  - Menambahkan fitur **Reverse Geocoding** otomatis dan dropdown wilayah Indonesia (Provinsi s/d Kelurahan).
  - Menambahkan pengaturan **Radius** (km) untuk toleransi jarak absensi.
- **Manajemen Karyawan (`UserResource`)**:
  - Menggabungkan fungsionalitas `KaryawanResource` ke dalam `UserResource` untuk manajemen terpusat.
  - Menambahkan fitur **Penugasan Gudang** (`gudang_id`) untuk menetapkan lokasi kerja karyawan.
  - Memperbaiki tombol "Tambah Karyawan" agar tampil inline di header halaman list.
  - **Fix Foto Profil**: Memperbaiki masalah gambar tidak tampil saat edit dengan menambahkan `visibility('public')` dan logika ekstraksi path JSON yang robust.
- **Validasi Absensi Geofencing**:
  - Mengimplementasikan validasi lokasi ketat pada `AbsensiResource` menggunakan koordinat gudang yang ditugaskan.
  - Menggunakan formula **Haversine** untuk perhitungan jarak akurat dan menolak absensi di luar radius gudang.
- **Perbaikan Sistem**:
  - Mengatasi masalah routing `MethodNotAllowedHttpException` pada login dengan pembersihan cache menyeluruh.
  - Mengoptimalkan struktur navigasi dengan menyembunyikan resource karyawan yang redundan.
  - **Fix Navigasi Filament Shield**: Memperbaiki menu **Roles** yang tersangkut di grup "Master Data". Masalah disebabkan oleh file terjemahan Indonesia (`resources/lang/vendor/filament-shield/id/filament-shield.php`) yang meng-override konfigurasi utama. Solusi: update file lang ke 'Pengaturan'.

## 2026.01.09
### Peningkatan Modul Penjadwalan Service (Service Center)
- **Fitur Crosscheck & Kelengkapan Unit**:
  - Mengimplementasikan sistem input checklist bertingkat (**Parent-Child**). Jika item induk dicentang, sub-item akan muncul.
  - Memisahkan atribut menjadi 4 kategori tab: **Crosscheck** (Fisik), **Aplikasi**, **Game**, dan **OS**.
  - Menambahkan halaman manajemen master data terpusat **"Atribut Crosscheck"** di bawah menu Penerimaan Service.
- **Import Data Pelanggan**:
  - Menambahkan fitur **"Import dari Nota Penjualan"** pada form service. User dapat mencari nomor nota, dan sistem otomatis mengisi data pelanggan (Nama, HP, Alamat) tanpa mengetik ulang.
- **Pencetakan Dokumen (Print Views)**:
  - **Pemisahan Dokumen**: Memisahkan "Cetak Invoice" (Tanda Terima untuk customer) dan "Cetak Checklist" (Lembar kerja teknisi/detail).
  - **Cetak Checklist**: Layout khusus A4 yang menampilkan seluruh detail item yang dicentang (Apps, Game, Kondisi fisik) dengan tampilan grid yang rapi.
- **Perbaikan UI/UX**:
  - **Grouped Actions**: Mengelompokkan tombol aksi tabel (View, Edit, Print) ke dalam satu menu dropdown (**Menu**) agar tampilan tabel lebih ringkas.
  - **Custom Saving Logic**: Mengimplementasikan logika penyimpanan relasi many-to-many kustom pada `Create` dan `Edit` page untuk menangani form dinamis.

## 2026.01.07
### Fitur Cetak Service & Perbaikan Sistem
- **Cetak Invoice Service**:
  - Membuat tampilan cetak (**print view**) yang rapi ala invoice untuk `PenjadwalanService`, lengkap dengan informasi dinamis perusahaan (Haen Komputer).
  - Menghapus kolom harga/subtotal untuk menyederhanakan tampilan (hanya perangkat & layanan), serta menyelaraskan teks "Nama Perangkat" ke kiri.
  - Menambahkan tombol aksi cetak praktis pada halaman *List* dan *View* service.
- **Perbaikan Bug & Environment**:
  - Mengatasi error `stty: invalid argument` saat menjalankan `php artisan shield:generate` dengan menambahkan flag `--panel=admin`. Gunakan php `artisan shield:generate --minimal --panel=admin`.
  - Memperbaiki error `SvgNotFound` pada **Filament Shield** yang disebabkan oleh isu *case-sensitivity* pada Linux (`APP_LOCALE=ID` vs folder `id`).
  - Menambahkan konfigurasi ketahanan (`resilience`) pada `config/app.php` untuk otomatis memaksa locale menjadi lowercase, sehingga memperbaiki error translasi global (dashboard, media manager).
- **Lokalisasi**:
  - Menambahkan file translasi Bahasa Indonesia manual untuk **Filament Media Manager** guna memperbaiki tampilan menu yang sebelumnya menampilkan kode raw (`Filament-media-manager::messages...`).
  - Mengubah grup navigasi **Shield/Peran** dari "Pelindung" menjadi "**Master Data**" melalui penyesuaian file translasi.

## 2026.01.06
### Perbaikan Fitur Database Backup & Restore
- Memperbaiki bug upload file database backup berukuran besar (>2MB) yang menyebabkan error "Upload gagal".
- Menambahkan **server-router.php** untuk menangani static files (CSS, JS, gambar) pada development server dengan benar.
- Memperbarui **ServeWithLink.php** untuk menggunakan PHP built-in server dengan konfigurasi upload yang lebih besar:
  - `upload_max_filesize = 128M`
  - `post_max_size = 130M`
  - `memory_limit = 512M`
  - `max_execution_time = 300s`
  - `max_input_time = 300s`
- Memperbarui **config/livewire.php**: meningkatkan `max_upload_time` dari 5 menit menjadi 30 menit untuk mendukung upload file besar.
- Menambahkan **public/.user.ini** untuk konfigurasi PHP upload pada development server lokal.

### Migrasi Infrastruktur Database & Perbaikan Bug (New)
- **Migrasi Database**: Memindahkan database aplikasi dari container Docker MySQL ke layanan MariaDB aaPanel host untuk menghemat memori (~400MB) dan menyatukan manajemen database.
- **Standarisasi Environment**:
  - Memperbarui `docker-compose.yml` dengan `profiles: ["local"]` agar file yang sama dapat digunakan untuk development (dengan DB container) dan production (tanpa DB container).
  - Menambahkan script automatisasi deployment `deploy.sh` yang menangani pull code, build container, migrasi database, dan pembersihan cache secara aman.
- **Perbaikan Koneksi Redis/Cache**:
  - Mengubah driver cache dari `database` ke `file` pada `.env` untuk mencegah error koneksi jaringan saat proses deployment/booting awal container.
- **Perbaikan Keamanan & Kompatibilitas**:
  - Menambahkan middleware autentikasi (`web`, `auth`) pada upload file Livewire untuk mencegah error 401 saat impor database.
  - Menghapus opsi `--skip-ssl` yang tidak didukung pada perintah backup/restore database untuk kompatibilitas dengan MariaDB client terbaru.
- **Konfigurasi Firewall**: Menambahkan aturan firewall (iptables & ufw) untuk mengizinkan komunikasi aman antara Docker container dan MariaDB host.
- **Fixed (Prioritas)**: Mengatasi 500 Error pada pembuatan `Pembelian` (Error `Grid::isContained`) dengan memperbarui paket Filament ke `v3.3.46` dan menambahkan langkah `composer install` pada `deploy.sh` untuk memastikan sinkronisasi library.
  - Menghapus view publish yang usang `resources/views/vendor/filament/components/loading-section.blade.php` yang menyebabkan konflik.

### Perbaikan Stabilitas & Coding (Update Terlupakan)
- **WebpUpload**: Menambahkan *failsafe mechanism* dan logging. Jika konversi gambar ke WebP gagal, sistem otomatis menggunakan file asli (fallback) agar upload tidak gagal total.
- **JadwalKalenderWidget**: Memperbaiki query event dengan menambahkan `user_id` (select) untuk memastikan validasi kepemilikan data berjalan benar.

### Konfigurasi Docker untuk Production
- Memperbarui **Dockerfile**:
  - Menambahkan paket `default-mysql-client` untuk mengaktifkan perintah `mysqldump` dan `mysql` yang dibutuhkan fitur export/import database.
  - Menambahkan konfigurasi PHP upload (128M) dan timeout (300s) melalui `/usr/local/etc/php/conf.d/uploads.ini`.
- Memperbarui **docker/nginx/conf.d/app.conf**:
  - Meningkatkan `client_max_body_size` dari 100M menjadi 128M.
  - Menambahkan pengaturan timeout untuk upload file besar: `client_body_timeout`, `send_timeout`, `proxy_read_timeout` (300s).
  - Menambahkan `fastcgi_read_timeout` dan `fastcgi_send_timeout` (300s) untuk operasi yang berjalan lama.

## 2025.12.28
- Menambahkan **Kode Akun** default (11, 12, 21, 22, 31, 41, 51, 52, 61, 71, 81) melalui seeder baru dan mengaitkannya ke `DatabaseSeeder` agar otomatis tersedia saat deploy/seed.
- Menyempurnakan **Laba Rugi Detail**: baris **Beban Usaha** kini diambil dari **Jenis Akun** dengan kode akun 51/52/61/81 dan baris **Pendapatan Lain‑lain** dari kode akun 41/71, termasuk pengurutan dan perhitungan total per jenis akun.
- Menyaring baris **Beban Usaha** agar hanya menampilkan nominal yang terisi (total 0 disembunyikan), serta menampilkan label berdasarkan **nama_jenis_akun** tanpa prefix kode.
- Menambahkan aksi **Export** berbentuk dropdown tunggal pada halaman detail laba rugi untuk **CSV/XLSX/PDF**, berikut template PDF khusus yang menyamai tampilan infolist.
- Menyesuaikan tampilan infolist laba rugi: format angka negatif menggunakan tanda minus, dan menambahkan jarak atas 10px pada baris judul tebal (kecuali **Pendapatan**).
- Menambahkan ikon pada tab **Bulanan/Detail** dan memastikan tab aktif tidak tersangkut ketika kembali dari detail ke list.
- Menambahkan badge **Kategori Akun** pada tabel **Jenis Akun** dengan label dan warna mengikuti enum.

## 2025.12.27
- Tidak ada perubahan terkomit di git pada tanggal ini.

## 2025.12.26
- Tidak ada perubahan terkomit di git pada tanggal ini.

## 2025.12.25
- Menambahkan **Laporan Neraca** lengkap (resource, list/view pages, model, migrasi kolom `kelompok_neraca`, dan enum **KelompokNeraca**).
- Menambahkan field **Kelompok Neraca** pada **Kode Akun** serta penyesuaian logika filter/komposisi laporan neraca berdasarkan kategori akun.
- Menyusun tampilan infolist **Neraca** (template tabel + layout) dan mendaftarkan resource neraca pada panel akunting.
- Menambahkan pengujian **InventoryResource** (render list, filter inventaris aktif, serta kalkulasi snapshot).
- Menambahkan bagian **Catatan Testing** di `README_PEST` untuk setup dan tips debugging.

## 2025.12.24
- Memoles tampilan **Laporan Absensi**: penyesuaian ikon jam hadir/keluar dengan warna status dan penegasan nama karyawan pada tabel.
- Menyempurnakan **Lembur** dan **Laporan Absensi**: ikon kolom informatif, dropdown action, lokalisasi tanggal Indonesia, serta ringkasan kehadiran berbentuk badge berwarna dengan ikon.
- Mengubah **Absensi** ke tampilan detail **Slide-over** dua kolom, merapikan wizard form, dan memastikan seluruh format tanggal berbahasa Indonesia.
- Merombak **Stock Adjustment**: layout 3 kolom, repeater inline, serta perbaikan bug pada field `created_at` dan konflik tipe `Action`.

## 2025.12.23
- Menambahkan integrasi **PWA** (laravel-pwa) beserta ikon/splash screen dan `serviceworker.js` untuk cache dasar.
- Memperluas modul **Laba Rugi** dan **Input Transaksi Toko** (filter tab, detail infolist, dan penyesuaian perhitungan subtotal).
- Menambahkan/menyelaraskan komponen tampilan untuk **Beban**, **Pembelian**, dan **Penjualan** pada laporan (infolist & livewire table), termasuk perbaikan format tampilan item.
- Menyempurnakan tampilan **Penjualan** (items & jasa) serta relasi tampilan laporan agar konsisten di halaman view.
- Memperhalus UI **Request Order**, **Stock Opname**, dan widget dashboard (Active Members, Recent Transactions, Top Selling Products).

## 2024.12.20
- Menginisialisasi berkas `changelog.md`.
- Menambahkan relation manager pada resource **Jasa**.

## 2024.12.19
- Menyempurnakan UI/UX header tabs.

## 2024.12.18
- Memperbaiki logika perhitungan laporan absensi (telat/jam hadir).

## 2024.12.17
- Menambahkan tombol **Create** dengan ikon plus pada header list view.

## 2024.12.16
- Mengoptimasi resource-panel pasca migrasi dan memperbaiki bug minor pasca merge.
- Menstabilkan widget absensi.

## 2024.12.15
- Memperbaiki widget kalkulasi penyesuaian stok.
- Mengintegrasikan pembaruan besar dari branch **Laporan-Cuti** dan menyinkronkan branch **Keuangan** ke main.
- Menambahkan **Modal Helper View**.

## 2024.12.14
- Menyelesaikan merge branch keuangan dan menyelesaikan konflik.
- Menyesuaikan format ekspor akuntansi.

## 2025.12.23
- Menambahkan ringkasan **Laporan Laba Rugi**: total penjualan (pendapatan) dan laba kotor (pendapatan - HPP), serta memperbarui judul halaman view agar menampilkan bulan laporan.
- Menyelaraskan perhitungan **Laporan Laba Rugi**: total penjualan kini mencakup penjualan produk + jasa, laba kotor/laba rugi dihitung dari total gabungan, serta memastikan bulan dengan penjualan jasa saja tetap muncul.
- Memperbaiki daftar tahun pada filter **Laporan Laba Rugi** agar mencakup tahun yang hanya memiliki data penjualan.
- Menambahkan pagination Livewire (25 baris per halaman) pada tabel daftar penjualan, beban, dan pembelian; total pada bagian bawah kini dihitung untuk seluruh bulan, bukan hanya halaman aktif.
- Menormalkan logika **Daftar Beban** agar data beban konsisten antara tabel dan agregasi bulanan dengan mengacu ke kategori transaksi atau kategori akun terkait.
- Menonaktifkan widget **TopExpensesTable** pada Laporan Input Transaksi tanpa menghapus kodenya.
- Menambahkan filter rentang waktu pada daftar **Input Transaksi Toko** (1m/3m/6m/1y/custom) dan tab header kategori (Aktiva, Pasiva, Pendapatan, Beban, Semua).

## 2025.12.22
- Mengotomatisasi kategori transaksi di **Input Transaksi Toko** berdasarkan Kode/Jenis Akun agar konsisten dengan klasifikasi akun.
- Menjadikan **Kode Akun** dan **Jenis Akun** sebagai submenu dari **Input Transaksi Toko**, termasuk perbaikan breadcrumb/heading di halaman list.
- Memperbaiki aksi `view/edit` pada beberapa resource akunting agar navigasi tidak lagi memicu error `GET livewire/update`.
- Menyesuaikan breadcrumb **Pengaturan Akunting** agar mengarah ke **AppDashboard**.
- Menyempurnakan tampilan dark mode untuk infolist **Detail Beban**, **Produk Pembelian**, dan **Produk Terjual** (hover, border/divider, header), plus penegasan garis pemisah via override CSS.
- Meningkatkan keterbacaan label total pada dark mode di tab laba rugi.

## 2025.12.21
- Tidak ada perubahan terkomit di git pada tanggal ini.

## 2025.12.20
- Menambahkan modul laporan laba rugi (resource Filament, halaman list/view, model, dan migrasi tabel).
- Menambahkan aset CSS/JS laporan (filament-reports) beserta dependensi pendukungnya.
- Menambahkan dokumen **PLUGIN.md** untuk daftar plugin pihak ketiga.
- Menyesuaikan alur redirect login/halaman utama agar memakai route **AppDashboard** per panel.
- *Merge* dengan branch `main`.

## 2025.12.19
- Menyelaraskan ikon widget tabel ke **Hugeicons** dan memperbaiki nama ikon yang salah.
- Mengaktifkan tampilan ikon/deskripsi pada **ServiceWidget** dengan Advanced Table Widget.
- Menambahkan dukungan `infolist` untuk aksi `view` pada widget **Service** dan **Tugas** (termasuk impor class yang dibutuhkan).
- Membuat widget dashboard lebih interaktif (dapat diklik) untuk navigasi ke detail pada berbagai widget statistik, tabel, cuaca, stok, dan tugas.
- Menyegarkan gaya tema admin serta komponen tabel/loader untuk mendukung widget interaktif.
- Menyesuaikan konfigurasi Tailwind dan stylesheet aplikasi/Filament agar tampilan widget konsisten.

## 2025.12.18
- Meningkatkan **UX mobile** untuk tabel `infolist` (scroll horizontal) dan tata letak bergaya tabel untuk daftar item **Pembelian**, **Penjualan**, dan **Request Order**.
- Menambahkan `infolist` **Penjualan** dengan total yang dikalkulasi (*computed totals*); total sekarang dihitung ulang otomatis berdasarkan item saat `create`, `update`, atau `delete`.
- Menambahkan modal pembuatan "Tambah Member" secara *inline* dari menu select member di **Penjualan**.
- Menyempurnakan `repeater` form **Request Order** (dependensi kategori → produk, *placeholders*, dan tampilan HPP/harga jual yang terisi otomatis dari harga *batch* terbaru).
- Memperbaiki beberapa error `500` terkait Filament (konflik import, enum/class yang tidak valid) dan memindahkan aksi `create/edit` **Pembelian** ke header halaman.
- Memindahkan aksi `create` & `edit` **Stock Adjustment** / **Stock Opname** ke header halaman (menghapus tombol aksi di bawah form) dan meningkatkan alur `create` Stock Adjustment agar redirect ke halaman edit untuk penambahan item.

## 2025.12.16
- Menambahkan `infolist` **Pembelian** untuk catatan pembelian (*purchase records*).
- Memperbaiki kompatibilitas pemformatan kolom `filament-export`.
- Memperbaiki label jamak (*plural labels*) dan teks tombol tambah; memperbaiki widget absensi dan bug minor lainnya.

## 2025.12.15
- Menambahkan konfigurasi **Docker** dan penyesuaian port **MySQL**.
- Memperkenalkan dukungan `view helper` untuk modal.
- Memperbaiki widget (termasuk stock adjustment), unduhan `infolist`, tombol ekspor, label jamak, dan *wiring* panel provider.
- Menggabungkan (*merge*) branch **Keuangan** dan **Laporan-Cuti** yang sedang berjalan.

## 2025.12.14
- Memperbaiki pemformatan ekspor akuntansi dan *merge* pembaruan keuangan.

## 2025.12.13
- Memperbarui navigasi **Finansial**.
- Menambahkan alur pengajuan cuti dan perbaikan ekspor; *merge* perubahan **Laporan-Cuti**.

## 2025.12.11
- Perbaikan minor sebelum beralih ke master data **Brand**; menyempurnakan penanganan *return* pada master data.
- Melanjutkan pengerjaan laporan keuangan.

## 2025.12.10
- Memperbaiki tab pada pengaturan keuangan dan masalah pada `infolist`.
- Menangani navigasi untuk *user* dan error akses role (`403`); menstabilkan penanganan role setelah *revert*.

## 2025.12.09
- Menambahkan **Jenis Akun** dan **Kode Akun** ke modul keuangan.
- Menandai modul keuangan sebagai selesai secara fungsional, menunggu perbaikan *breadcrumb*.

## 2025.12.08
- Melanjutkan pengerjaan jenis akun/kode akun untuk pengaturan keuangan.

## 2025.12.07
- Membangun tampilan **Dashboard** dan persiapan perubahan kebijakan role (*policy*).
- Menerapkan `view policy` untuk catatan absensi aktif; memperbaiki penanganan role di widget utama dan widget terkait.

## 2025.12.05
- Penyesuaian dan pemolesan UI (*Polish*).

## 2025.12.04
- Memperbaiki role **Filament Shield**; menyempurnakan dashboard POS dan widget karyawan.
- Menambahkan widget cuaca; *merge* branch reporting.

## 2025.12.03
- Menetapkan tampilan dan nuansa UI baru; menyelaraskan navigasi master data.
- *Merge* perubahan dari branch `main`.

## 2025.12.01
- Menambahkan dokumentasi dan penyesuaian tata letak (navigasi, layout adjustments hingga lembur).
- Memperbarui gaya kolom; menyempurnakan penanganan *return* dan total pada **POS**.
- Meningkatkan **Navigation Bar** (termasuk sidebar yang dapat digeser/*draggable*), memodernisasi navigasi, ikon, dan perbaikan minor `infolist`/produk.
- *Merge* branch **POS** dan **Pengaturan-Navigasi**.

## 2025.11.30
- Memperbarui *navigation bar* dengan sidebar navigasi yang dapat digeser.

## 2025.11.29
- Meningkatkan *wizard* **Absensi** dan logika *check-in*.

## 2025.11.28
- Menambahkan penjadwalan servis, peningkatan pengiriman, dan peningkatan **POS** multi-aplikasi.
- *Merge* branch **POS** kembali ke `main`.

## 2025.11.27
- Commit persiapan sebelum *branching* untuk penjadwalan tugas (*task scheduling*); push terakhir untuk pekerjaan yang ada.

## 2025.11.26
- Menghapus cache `npm` yang ikut terkomit.
- Menambahkan penanganan **Lembur** (*Overtime*).
- *Merge* alur kerja inventaris, penjualan, dan pembelian dengan pembaruan absensi.

## 2025.11.24
- Menambahkan fitur **Absensi-Libur-Cuti** dan mengurutkan ulang alasan cuti/libur.
- Meningkatkan perilaku *collapse* sidebar pada desktop.

## 2025.11.23
- Menambahkan pengambilan foto (*photo capture*) untuk absensi dan tampilan `infolist` absensi.

## 2025.11.22
- Upgrade ke **Filament Shield** untuk *roles/permissions*.
- *Merge* branch **Absensi**; penyesuaian stock opname; otomatis menandai alpha untuk absensi terlambat.

## 2025.11.21
- Menambahkan plugin mata uang (*currency*) dan halaman **Company Profile**.
- Otomatis memilih karyawan yang sedang login, waktu, dan tanggal dengan penanganan UTC+7.

## 2025.11.20
- Rename modul **POS**; *merge* branch **POS** ke `main`.
- Menambahkan laporan penjualan dan pembelian; *auto-fetch* longitude/latitude.

## 2025.11.19
- Menambahkan pembuatan **Penjualan** dan alurnya.
- Meningkatkan obrolan grup **Chatify** dengan daftar anggota dan avatar; menginstal media manager sebagai persiapan POS.

## 2025.11.18
- Menambahkan fitur **Pembelian** dan **Inventaris**; menyesuaikan akun transaksi dan penyelarasan master data.
- Melanjutkan perbaikan inventaris dan memperkenalkan prototipe ruang obrolan (**Chatify**).

## 2025.11.16
- Mengimplementasikan pemrosesan **Akun Transaksi**.

## 2025.11.15
- Menambahkan pengaturan *roles & permissions*, pendaftaran pengguna, dan halaman login/pemilihan role untuk karyawan.
- Memperkenalkan alur **Request Order**.
- *Merge* branch **Master-Data** dan **Inventaris**.

## 2025.11.14
- Menambahkan manajemen hubungan member, supplier, dan agen.
- Menambahkan master data **Gudang** dan menyatukan tata letak tabel dengan foto profil.
- Memperkenalkan fondasi *role/permission/authentication*.

## 2025.11.13
- Menyelesaikan master data inti untuk **Brand**, **Jasa**, **Kategori**, dan **Produk**.

## 2025.11.12
- Menambahkan kerangka dasar (*scaffolding*) master data awal.

## 2025.11.11
- Project dimulai
- Membuat migrasi (*migrations*) untuk tabel produk, jasa, brand, dan kategori (awal proyek).
