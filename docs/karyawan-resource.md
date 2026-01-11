# Dokumentasi Karyawan Resource

**Tanggal Update**: 2026-01-11  
**Status**: Aktif (dikelola via UserResource)

---

## Overview

Karyawan Resource adalah modul untuk mengelola data karyawan/pegawai perusahaan. Sejak update terbaru, pengelolaan data Karyawan **digabungkan ke dalam UserResource** untuk menyederhanakan manajemen user dan profil karyawan dalam satu tampilan.

### Arsitektur Data

```
┌─────────────────────────────────────────────────────────────┐
│                        UserResource                          │
│  (app/Filament/Resources/UserResource.php)                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   ┌─────────────────┐         ┌──────────────────────┐      │
│   │   users table   │   1:1   │   md_karyawan table  │      │
│   │   (User Model)  │◄───────►│  (Karyawan Model)    │      │
│   └─────────────────┘         └──────────────────────┘      │
│                                                              │
│   Data:                        Data:                         │
│   - id                         - nama_karyawan               │
│   - name                       - slug                        │
│   - email                      - telepon                     │
│   - password                   - alamat                      │
│   - roles (Spatie)            - provinsi, kota, dll          │
│                                - dokumen_karyawan            │
│                                - image_url (foto profil)     │
│                                - is_active                   │
│                                - role_id                     │
└─────────────────────────────────────────────────────────────┘
```

---

## Struktur File

### Resource Files

| File | Lokasi | Deskripsi |
|------|--------|-----------|
| `UserResource.php` | `app/Filament/Resources/` | Resource utama untuk kelola User + Karyawan |
| `CreateUser.php` | `app/Filament/Resources/UserResource/Pages/` | Page untuk buat user baru |
| `EditUser.php` | `app/Filament/Resources/UserResource/Pages/` | Page untuk edit user |
| `ListUsers.php` | `app/Filament/Resources/UserResource/Pages/` | Page list/tabel users |
| `ViewUser.php` | `app/Filament/Resources/UserResource/Pages/` | Page view detail user |

### Model Files

| File | Lokasi | Deskripsi |
|------|--------|-----------|
| `User.php` | `app/Models/` | Model utama untuk autentikasi & roles |
| `Karyawan.php` | `app/Models/` | Model profil karyawan (relasi ke User) |

### Legacy Resource (Disembunyikan)

| File | Lokasi | Status |
|------|--------|--------|
| `KaryawanResource.php` | `app/Filament/Resources/MasterData/` | **Disembunyikan** dari navigasi |

---

## Database Schema

### Tabel `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    messenger_color VARCHAR(255) NULL,
    dark_mode BOOLEAN DEFAULT false,
    active_status BOOLEAN DEFAULT true,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Tabel `md_karyawan`

```sql
CREATE TABLE md_karyawan (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nama_karyawan VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    telepon VARCHAR(20) NULL,
    alamat TEXT NULL,
    provinsi VARCHAR(100) NULL,
    kota VARCHAR(100) NULL,
    kecamatan VARCHAR(100) NULL,
    kelurahan VARCHAR(100) NULL,
    dokumen_karyawan JSON NULL,
    image_url VARCHAR(255) NULL,
    user_id BIGINT UNSIGNED NULL,
    role_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);
```

---

## Fitur Form

### Section: Informasi Personal

| Field | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| Nama Lengkap | TextInput | ✅ | Nama sesuai KTP, otomatis di-title-case |
| Slug | TextInput | ✅ | Auto-generate dari nama, read-only |
| No. Handphone/WA | TextInput | ✅ | Format: 08xxxxxxxxxx |

### Section: Alamat Domisili

| Field | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| Alamat Lengkap | Textarea | ❌ | Jalan, RT/RW, Nomor Rumah |
| Provinsi | Select | ❌ | Dropdown dari laravolt/indonesia |
| Kota/Kabupaten | Select | ❌ | Dependent pada Provinsi |
| Kecamatan | Select | ❌ | Dependent pada Kota |
| Kelurahan/Desa | Select | ❌ | Dependent pada Kecamatan |

### Section: Kelengkapan Dokumen

| Field | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| Jenis Dokumen | TextInput | ✅ | Contoh: KTP, Ijazah S1, CV |
| Upload File | FileUpload | ❌ | Accept: PDF, Image. Max: 5MB |

Menggunakan **Repeater** component untuk multiple dokumen.

### Section: Foto Profil

| Field | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| Foto Wajah | FileUpload | ❌ | Avatar circular, dengan image editor |

Fitur:
- Circle cropper
- Image editor (crop, rotate)
- Auto-convert ke WebP
- Naming: `{tanggal}-{slug-nama}.webp`

### Section: Akses Sistem

| Field | Type | Required | Deskripsi |
|-------|------|----------|-----------|
| Status Akun Aktif | Toggle | ❌ | Default: ON |
| Role/Jabatan | Select | ✅ | Dari Spatie roles |
| Ubah Email? | Toggle | ❌ | Hanya muncul saat Edit |
| Ubah Password? | Toggle | ❌ | Hanya muncul saat Edit |
| Email Login | TextInput | ✅ | Unique, disabled saat edit (kecuali toggle ON) |
| Password | TextInput | ✅* | Required hanya saat Create |
| Ulangi Password | TextInput | ❌ | Validasi same as Password |

---

## Tabel Columns

| Column | Type | Sortable | Searchable | Toggleable |
|--------|------|----------|------------|------------|
| Foto Profil | ImageColumn | ❌ | ❌ | ❌ |
| Karyawan | TextColumn | ✅ | ✅ | ❌ |
| WhatsApp | TextColumn | ✅ | ✅ | ✅ |
| Role | TextColumn (Badge) | ✅ | ✅ | ✅ |
| Terdaftar | TextColumn | ✅ | ❌ | ❌ |
| Aktif | IconColumn | ❌ | ❌ | ❌ |

---

## Permissions

Resource ini menggunakan **Filament Shield** untuk permission management.

| Permission | Deskripsi |
|------------|-----------|
| `view_any_user` | Melihat semua data karyawan |
| `view_limit_user` | Hanya melihat data sendiri |
| `create_user` | Membuat karyawan baru |
| `update_user` | Mengubah data karyawan |
| `delete_user` | Menghapus karyawan |

---

## Relasi dengan Resource Lain

Model `Karyawan` digunakan oleh resource lain sebagai foreign key:

| Resource | Field | Penggunaan |
|----------|-------|------------|
| `PenjualanResource` | `id_karyawan` | Kasir/petugas penjualan |
| `PembelianResource` | `id_karyawan` | Petugas pembelian |
| `TukarTambahResource` | `id_karyawan` | Petugas tukar tambah |
| `RequestOrderResource` | `karyawan_id` | Petugas request order |
| `PenjadwalanTugasResource` | `karyawan_id` | Petugas yang ditugaskan |
| `PenjadwalanPengirimanResource` | `karyawan_id` | Driver/kurir pengiriman |
| `LiburCutiResource` | `user_id` | Karyawan yang cuti |

---

## Hooks & Lifecycle

### CreateUser.php

```php
protected function afterCreate(): void
{
    // Membuat record Karyawan setelah User berhasil dibuat
    $this->record->karyawan()->create([
        'nama_karyawan' => $karyawanData['nama_karyawan'],
        'slug' => $karyawanData['slug'],
        'telepon' => $karyawanData['telepon'],
        // ... field lainnya
    ]);
}
```

### EditUser.php

```php
protected function mutateFormDataBeforeFill(array $data): array
{
    // Load data Karyawan ke form saat edit
    $data['karyawan'] = [
        'nama_karyawan' => $karyawan->nama_karyawan,
        // ... field lainnya
    ];
    return $data;
}

protected function afterSave(): void
{
    // Update atau create Karyawan setelah User disimpan
    if ($karyawan) {
        $karyawan->update($updateData);
    } else {
        $this->record->karyawan()->create($updateData);
    }
}
```

---

## URL Routes

| Route | Deskripsi |
|-------|-----------|
| `/admin/users` | List semua karyawan |
| `/admin/users/create` | Form tambah karyawan baru |
| `/admin/users/{id}` | View detail karyawan |
| `/admin/users/{id}/edit` | Edit karyawan |

---

## Troubleshooting

### Data Karyawan tidak muncul saat Edit

**Penyebab**: User tidak memiliki relasi Karyawan.

**Solusi**: Pastikan hook `afterCreate` di `CreateUser.php` berjalan dengan benar dan membuat record Karyawan.

### Foto tidak tersimpan

**Penyebab**: Disk storage tidak dikonfigurasi dengan benar.

**Solusi**: 
1. Pastikan disk `public` sudah di-symlink: `php artisan storage:link`
2. Check permission folder `storage/app/public/karyawan/foto`

### Dropdown Provinsi/Kota kosong

**Penyebab**: Package `laravolt/indonesia` belum di-seed.

**Solusi**: Jalankan seeder Indonesia: `php artisan laravolt:indonesia:seed`

---

## Changelog

| Tanggal | Perubahan |
|---------|-----------|
| 2026-01-11 | Migrasi pengelolaan Karyawan ke UserResource |
| 2026-01-11 | Sembunyikan KaryawanResource dari navigasi |
| 2026-01-11 | Tambah hooks untuk sinkronisasi data User ↔ Karyawan |
