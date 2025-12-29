# Catatan Perubahan (Changelog)

Semua perubahan penting pada proyek ini direkonstruksi dari riwayat git. Pembuatan versi sekarang mengikuti sistem CalVer (`YYYY.MM.DD`) selama aplikasi masih dalam tahap pra-1.0. Entri disusun secara kronologis dengan perubahan terbaru berada di paling atas.

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
