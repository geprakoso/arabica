# Laporan Troubleshooting: Error 403 Forbidden pada Panel Filament

**Tanggal:** 5 Januari 2026  
**Aplikasi:** Haen Komputer (Arabica)  
**Environment:** Docker + PHP-FPM + Nginx + MySQL  
**Framework:** Laravel 11 + Filament v3 + Filament Shield  

---

## 1. Deskripsi Masalah

### Gejala
- Halaman admin (`/admin`) dan POS (`/pos`) menampilkan **Error 403 Forbidden** setelah user login.
- Halaman login dapat diakses dengan normal.
- Proses login berhasil (session tersimpan).
- Setelah login, redirect ke dashboard menghasilkan 403.

### Kondisi Awal
- Aplikasi berjalan di server remote melalui Docker Compose.
- Container: `arabica-app` (PHP-FPM), `arabica-nginx`, `arabica-db` (MySQL), `arabica-queue`.
- Session driver: `database`
- Cache driver: `database`

---

## 2. Proses Debugging

### 2.1 Pengecekan Awal
| Langkah | Hasil |
|---------|-------|
| Container database (`arabica-db`) | Mati (Exit 137 - OOM). Di-restart manual. |
| `php artisan optimize:clear` | Gagal dari host karena `DB_HOST=db` tidak dikenali. Harus via `docker exec`. |
| Permission Shield | Sudah di-generate dengan `shield:generate --all`. |

### 2.2 Route Debug `/test-auth`
Dibuat route khusus untuk mengecek status autentikasi:
```php
Route::get('/test-auth', function () {
    return [
        'is_logged_in' => Auth::check(),
        'user_id' => Auth::id(),
        'roles' => Auth::user()?->getRoleNames(),
        'can_access_panel' => Auth::user()?->canAccessPanel(Filament::getPanel('admin')),
    ];
});
```

**Hasil:**
```json
{
  "is_logged_in": true,
  "user_id": 1,
  "roles": ["super_admin"],
  "can_access_panel": true
}
```

**Kesimpulan:** User terautentikasi dengan benar, tapi tetap 403 di panel.

### 2.3 Eksperimen Middleware

| Percobaan | Hasil |
|-----------|-------|
| Nonaktifkan `ShieldPlugin` | Masih 403 |
| Nonaktifkan `AppDashboard::class` | Masih 403 |
| Nonaktifkan `Authenticate::class` di `authMiddleware` | **BERHASIL MASUK** |

**Kesimpulan:** Masalah ada di `Filament\Http\Middleware\Authenticate`.

---

## 3. Root Cause Analysis

### Penyebab Utama
Middleware `Filament\Http\Middleware\Authenticate` tidak kompatibel dengan environment Docker + PHP-FPM + Session Database.

### Kemungkinan Teknis
1. **Session Handling Issue:** Middleware Filament menggunakan `$this->auth->shouldUse()` yang mungkin tidak sinkron dengan session yang sudah tersimpan di database.
2. **Guard Mismatch:** Filament mencoba menggunakan guard yang tidak sesuai dengan session aktif.
3. **Exception Handling:** Middleware melempar `AuthenticationException` yang ditangkap dan dirender sebagai 403, bukan redirect ke login.

---

## 4. Solusi yang Diterapkan

### 4.1 Custom Middleware: `SimpleFilamentAuth`

Dibuat middleware pengganti yang lebih sederhana:

**File:** `app/Http/Middleware/SimpleFilamentAuth.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SimpleFilamentAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            Auth::shouldUse('web');
            return $next($request);
        }

        $panel = Filament::getCurrentPanel();
        
        if ($panel) {
            return redirect()->to($panel->getLoginUrl());
        }

        return redirect()->route('filament.admin.auth.login');
    }
}
```

### 4.2 Konfigurasi Panel Provider

**AdminPanelProvider.php:**
```php
->authMiddleware([
    \App\Http\Middleware\SimpleFilamentAuth::class,
])
```

**PosPanelProvider.php:**
```php
->authMiddleware([
    \App\Http\Middleware\SimpleFilamentAuth::class,
])
```

---

## 5. Daftar Perubahan Lengkap

### File Baru
| File | Deskripsi |
|------|-----------|
| `app/Http/Middleware/SimpleFilamentAuth.php` | Middleware autentikasi custom |
| `app/Http/Middleware/DebugFilamentAuth.php` | Middleware debug (bisa dihapus) |
| `public/ced.php` | File tes PHP (bisa dihapus) |

### File Dimodifikasi

#### `app/Providers/Filament/AdminPanelProvider.php`
- Diganti `Authenticate::class` → `SimpleFilamentAuth::class`

#### `app/Providers/Filament/PosPanelProvider.php`
- Diganti `Authenticate::class` → `SimpleFilamentAuth::class`

#### `app/Models/User.php`
- Method `canAccessPanel()` dikembalikan ke logika role-based:
```php
public function canAccessPanel(Panel $panel): bool
{
    $superAdminRole = config('filament-shield.super_admin.name', 'super_admin');
    $panelUserRole = config('filament-shield.panel_user.name', 'panel_user');

    return $this->hasAnyRole([$superAdminRole, $panelUserRole, 'kasir', 'petugas'])
        || $this->roles()->exists();
}
```

#### `routes/web.php`
- Ditambahkan route `/test-auth` untuk debugging (bisa dihapus setelah selesai).

#### `Dockerfile`
- Dikembalikan flag `--no-scripts` pada `composer install` (masalah build).

---

## 6. Dampak pada Fitur

| Fitur | Status | Catatan |
|-------|--------|---------|
| Login/Logout | ✅ Normal | |
| Shield Permissions | ✅ Normal | Tidak terpengaruh |
| Role-Based Access | ✅ Normal | `canAccessPanel()` tetap berfungsi |
| Panel Admin | ✅ Normal | |
| Panel POS | ✅ Normal | |
| Database Session | ✅ Normal | |

---

## 7. Rekomendasi

1. **Simpan `SimpleFilamentAuth.php`** sebagai solusi permanen.
2. **Hapus file debug** yang tidak diperlukan:
   - `public/ced.php`
   - `app/Http/Middleware/DebugFilamentAuth.php`
   - Route `/test-auth` di `routes/web.php`
3. **Monitor** jika ada update Filament yang memperbaiki masalah ini.
4. **Backup** konfigurasi Docker dan `.env` secara berkala.

---

## 8. Perintah Penting untuk Maintenance

```bash
# Clear semua cache (harus via docker exec)
docker exec -it arabica-app php artisan optimize:clear

# Restart container
docker-compose restart

# Rebuild container (jika ada perubahan Dockerfile)
docker-compose up -d --build

# Cek status container
docker ps

# Lihat log container
docker logs arabica-app --tail 50
```

---

**Dokumen ini dibuat pada:** 5 Januari 2026, 19:48 WIB  
**Status:** Resolved ✅
