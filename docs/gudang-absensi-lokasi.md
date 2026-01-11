# Dokumentasi Fitur: Gudang & Absensi Berbasis Lokasi

Dokumen ini menjelaskan implementasi fitur manajemen lokasi gudang yang terintegrasi dengan data karyawan dan validasi absensi (geofencing).

## 1. Manajemen Lokasi Gudang (`GudangResource`)

Fitur ini memungkinkan admin untuk menentukan titik koordinat dan alamat lengkap gudang menggunakan peta interaktif.

### Fitur Utama:
- **Interactive Map Picker**: Menggunakan Leaflet.js (OpenStreetMap) untuk memilih lokasi secara visual.
- **Auto-Coordinate**: Latitude dan Longitude terisi otomatis saat marker digeser atau lokasi dipilih.
- **Reverse Geocoding**: Mengambil alamat lengkap (Jalan, Kota, dll) otomatis dari Nominatim API saat pin dijatuhkan.
- **Wilayah Indonesia**: Dropdown berjenjang untuk Provinsi, Kota, Kecamatan, dan Kelurahan menggunakan package Laravolt.
- **Radius**: Pengaturan radius (dalam meter/km) untuk toleransi jarak absensi.

### Implementasi Teknis:
- **Plugin**: `dotswan/filament-map-picker`
- **Model**: `Gudang` (kolom: `latitude`, `longitude`, `radius_km`, `provinsi`, `kota`, `kecamatan`, `kelurahan`)
- **Migrasi**: Kolom lokasi sudah tersedia di tabel `md_gudang`, tidak ada migrasi baru yang diperlukan (masalah duplikat kolom telah diselesaikan).

## 2. Integrasi Karyawan (`UserResource`)

Data karyawan kini dikelola sepenuhnya di dalam `UserResource`, termasuk penugasan lokasi kerja.

### Perubahan Signifikan:
- **Merged Resource**: `KaryawanResource` disembunyikan; semua fitur dipindah ke `UserResource`.
- **Penugasan Gudang**: Field `gudang_id` (Select) ditambahkan untuk menetapkan lokasi kerja spesifik bagi karyawan.
- **Foto Profil**:
  - Perbaikan mekanisme upload menggunakan disk `public`.
  - **Fix**: Logika ekstraksi path dari format JSON Filament pada `CreateUser` dan `EditUser` hook (`reset($imageUrl)`).
  - **Fix**: Penambahan `visibility('public')` agar gambar tampil di form edit.

## 3. Validasi Absensi Geofencing (`AbsensiResource`)

Sistem absensi kini memvalidasi lokasi karyawan berdasarkan gudang yang ditugaskan.

### Logika Validasi:
1. **Cek Penugasan**: Sistem memastikan karyawan memiliki `gudang_id`.
2. **Cek Koordinat**: Mengambil `latitude` & `longitude` dari gudang terkait.
3. **Deteksi Lokasi**: Menggunakan Geolocation API browser untuk lokasi karyawan saat ini.
4. **Hitung Jarak**: Menggunakan formula **Haversine** untuk menghitung jarak akurat dalam meter.
5. **Geofencing**: Jika jarak > radius gudang (default 50m), absensi **ditolak**.

```php
// Snippet Logika Validasi (CreateAbsensi.php)
$jarak = $this->hitungJarak($gudang->lat, $gudang->long, $userLat, $userLong);
if ($jarak > $gudang->radius_km) {
    // Tolak Absensi
}
```

## 4. Troubleshooting Login (Routing)

Masalah `MethodNotAllowedHttpException` pada login (POST route missing) diatasi dengan:
- Pembersihan cache routing & config: `php artisan optimize:clear`
- Memastikan tidak ada middleware yang memblokir method POST.
- Masalah teridentifikasi berkaitan dengan cache browser/device, bukan kode backend.
