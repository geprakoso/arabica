# Dokumentasi Perbaikan Sinkronisasi Avatar User & Karyawan

**Tanggal:** 12 Januari 2026  
**Penulis:** Antigravity (Assistant)  
**Terkait:** `joaopaulolndev/filament-edit-profile`, `User` Model, `Karyawan` Model

---

## 1. Latar Belakang Masalah

Pada awalnya, sistem mengalami beberapa kendala terkait foto profil pengguna:
1.  **Gambar Tidak Muncul di Form Edit Profil:** Plugin `filament-edit-profile` menampilkan lingkaran kosong (placeholder) meskipun user sudah memiliki foto profil.
2.  **Isu Sinkronisasi Data:** Data avatar tersimpan di tabel `md_karyawan` (`image_url`), sedangkan plugin mengharapkan kolom di tabel `users`. Penggunaan *Virtual Attribute* (`getAvatarUrlAttribute`) tidak dikenali dengan baik oleh *FileUpload* component plugin.
3.  **Error 403 Forbidden:** Foto yang diupload melalui form profil tidak bisa diakses (broken image) karena tersimpan di disk `private` (default `local`), bukan `public`.

---

## 2. Analisis Teknis

*   **Penyebab Sinkronisasi:** Plugin `filament-edit-profile` secara default melakukan *binding* langsung ke kolom database pada model `User`. Karena `avatar_url` sebelumnya hanyalah *accessor* (bukan kolom fisik), Livewire gagal memuat nilai awal (*hydrate*) ke dalam komponen `FileUpload`.
*   **Penyebab 403 Forbidden:** Konfigurasi `.env` menggunakan `FILESYSTEM_DISK=local`. Tanpa konfigurasi eksplisit pada plugin, file terupload ke `storage/app/private/` yang tidak dapat diakses via browser.

---

## 3. Langkah-Langkah Perbaikan

Kami melakukan serangkaian perbaikan struktural untuk memastikan fitur ini berjalan stabil jangka panjang.

### A. Migrasi Database (Menambah Kolom Phisik)

Kami menambahkan kolom `avatar_url` pada tabel `users` sebagai *Single Source of Truth* untuk plugin, namun tetap menjaga sinkronisasi ke tabel `karyawan` untuk kompatibilitas fitur lama.

**File Migrasi:** `database/migrations/2026_01_12_131704_add_avatar_url_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('avatar_url')->nullable()->after('avatar');
});

// Migrasi data lama dari karyawan ke users
DB::statement("
    UPDATE users u
    LEFT JOIN md_karyawan k ON k.user_id = u.id
    SET u.avatar_url = k.image_url
    WHERE k.image_url IS NOT NULL
");
```

### B. Refactoring Model User (Pola Observer)

Kami mengubah logika di `App/Models/User.php`. Alih-alih menggunakan *Mutator* yang kompleks, kami menggunakan **Model Observer** yang lebih bersih untuk menyinkronkan data.

1.  **Menghapus Virtual Attribute:** Menghapus `getAvatarUrlAttribute` dan `setAvatarUrlAttribute` serta menghapus dari `$appends`.
2.  **Menambah Casting:** Menambahkan cast `'avatar_url' => 'string'` agar tipe data konsisten.
3.  **Implementasi `booted()`:** Menambahkan logika sinkronisasi otomatis. Setiap kali `avatar_url` di tabel `users` berubah, sistem otomatis mengupdate `image_url` di tabel `karyawan`.

```php
protected static function booted(): void
{
    static::saved(function (User $user) {
        if ($user->wasChanged('avatar_url')) {
            // Update atau Create data Karyawan
            if ($user->karyawan) {
                $user->karyawan->image_url = $user->avatar_url;
                $user->karyawan->save();
            } else {
                // Logic create karyawan baru...
            }
        }
    });
}
```

### C. Konfigurasi Plugin (Memperbaiki 403 Error)

Kami memperbarui konfigurasi plugin untuk memaksa penggunaan disk `public`, terlepas dari pengaturan default server.

**File Config:** `config/filament-edit-profile.php`

```php
return [
    'avatar_column' => 'avatar_url',
    'disk' => 'public',   // SEBELUMNYA: env('FILESYSTEM_DISK', 'public') -> DIUBAH JADI 'public'
    'visibility' => 'public',
];
```

Perubahan ini menjamin file selalu tersimpan di `storage/app/public/karyawan/foto/` yang dapat diakses publik.

### D. Update UserResource

Kami juga memperbarui `App/Filament/Resources/UserResource.php` agar menggunakan kolom `avatar_url` pada form upload dan tabel.

*   Form Upload: `Forms\Components\FileUpload::make('avatar_url')`
*   Tabel Column: `ImageColumn::make('avatar_url')`

Ini memastikan bahwa upload dari admin panel (Menu Karyawan) juga memicu *Observer* di model User, sehingga sinkronisasi tetap berjalan dua arah.

---

## 4. Hasil Verifikasi

1.  **Upload Foto:** Berhasil upload foto baru via halaman "My Profile" maupun menu "Master Data > Karyawan".
2.  **Preview:** Foto muncul dengan benar di preview form upload (tidak lagi lingkaran kosong).
3.  **Top Bar Avatar:** Avatar di pojok kanan atas muncul sesuai foto yang diupload.
4.  **Akses File:** File dapat diakses via URL langsung tanpa error 403.
5.  **Sinkronisasi:** Perubahan di tabel `users` otomatis ter-copy ke tabel `md_karyawan`.

---

**Catatan Tambahan:**
File avatar yang sebelumnya tersimpan di *private storage* telah dipindahkan secara manual ke *public storage* selama proses perbaikan ini.
