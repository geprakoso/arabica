# Changelog - Arabica Management System

## 2025-12-25 (Thursday)
### Testing Updates
- **InventoryResource**: Added Pest coverage for list rendering, active inventory filtering, and snapshot calculations.
- **Testing Notes**: Added a "Catatan Testing" section in README_PEST with setup and debugging tips.

## 2025-12-24 (Wednesday)
### 17:00 — Final UI Polish
- **LaporanAbsensiResource**:
    - **Visual Indicators**: Ikon `Jam Kehadiran` diubah menjadi Hijau (Success) dan `Jam Keluar` menjadi Merah (Danger).
    - **Focus Points**: Nama karyawan pada tabel menggunakan format **Bold** untuk mempermudah identifikasi data.

### 16:00 — Attendance Reporting & Overtime
- **LemburResource**:
    - **Resource Revamp**: Penambahan ikon informatif pada kolom Karyawan, Tanggal, dan Waktu.
    - **Action Group**: Integrasi menu aksi ke dalam dropdown (`heroicon-m-ellipsis-vertical`) untuk menyederhanakan tampilan tabel.
    - **Localization**: Implementasi otomatis lokalisasi penanggalan Bahasa Indonesia.
- **LaporanAbsensiResource**:
    - **Enhanced Summary**: Transformasi rekapitulasi kehadiran (Hadir, Izin, Sakit) menjadi sistem **Badge Berwarna** dengan ikon.
    - **Detailed Analytics**: Penambahan deskripsi email karyawan, nama hari otomatis pada tanggal, dan ikon navigasi fungsional pada jam masuk/keluar.

### 13:00 — Workflow & Slide-over Integration
- **AbsensiResource**:
    - **Slide-over Infolist**: Implementasi layout **Split 2-kolom**. Sisi kiri menonjolkan bukti foto, sisi kanan berisi rincian data karyawan, waktu, dan lokasi.
    - **Navigation Optimization**: Menghapus rute/halaman "View" terpisah dan menggantinya sepenuhnya dengan detail **Slide-over** yang lebih ringan.
    - **Form Wizard Refinement**: Perataan layout form input absensi dengan section yang bersih, segmented buttons untuk status, dan collapsible section untuk koordinat lokasi.
    - **Localization**: Memastikan seluruh representasi tanggal menggunakan standar Bahasa Indonesia.

### 12:00 — Stock Adjustment Overhaul
- **StockAdjustmentResource**:
    - **Three-Column Grid**: Restrukturisasi layout form menjadi 3 kolom (Main Content + Info Sidebar).
    - **Inline Repeater**: Mengganti Relation Manager tradisional dengan Filament **Repeater** untuk manajemen item barang secara inline tanpa berpindah halaman.
    - **Bug Fixes**:
        - Migrasi `TextInput` ke `Placeholder` pada field `created_at` untuk mencegah error runtime.
        - Resolusi konflik tipe data `Action` pada fungsi penciutan (*collapse*) item di internal repeater.

---

## 2025-12-23 (Tuesday)
### 23:00 — PWA Integration
- **Progressive Web App (PWA)**:
    - **PWA Capabilities**: Integrasi `laravel-pwa` untuk fungsionalitas "Add to Home Screen".
    - **Visual Assets**: Implementasi set ikon lengkap dan splash screens untuk standar Android/iOS.
    - **Service Worker**: Penambahan `serviceworker.js` untuk manajemen cache dan dukungan dasar mode offline.

### 22:00 — Inventory UI Refinement
- **Modules Update**:
    - **RequestOrderResource**: Pembersihan layout tabel dan form serta pengayaan ikon visual.
    - **StockOpnameResource**: Redesain UI untuk proses audit stok agar lebih fokus dan informatif.
    - **Dashboard Widgets**: Penyempurnaan estetik pada widget `Active Members`, `Recent transactions`, dan `Top Selling Products`.

---

## 2024-12-14 – 2024-12-20
### 2024-12-20
- `[197276c]` Inisialisasi berkas `changelog.md`.
- `[4605483]` Implementasi Relation Manager pada resource jasa.

### 2024-12-19
- `[b4264d9]` Penyempurnaan UI/UX Header Tabs.

### 2024-12-18
- `[6b754ec]` Perbaikan logika perhitungan laporan absensi (telat/jam hadir).

### 2024-12-17
- `[0b061d7]` Penambahan tombol Create dengan ikon plus pada header list view.

### 2024-12-16
- `[3c83af1]` Optimasi awal resource-panel pasca migrasi.
- `[67e5219]` Resolusi bug minor pasca penggabungan cabang.
- `[db4b375]` Stabilisasi widget absensi.

### 2024-12-15
- `[8a99906]` Perbaikan widget kalkulasi penyesuaian stok.
- `[0d3eeb0]` Integrasi pembaruan besar dari cabang `Laporan-Cuti`.
- `[66ec7f4]` Sinkronisasi cabang `keuangan` ke mainline.
- `[cb10f3e]` Implementasi Modal Helper View.

### 2024-12-14
- `[8493c5b]` Finalisasi penggabungan cabang keuangan dan resolusi konflik.
- `[78cddb6]` Penyesuaian format ekspor akuntansi.
