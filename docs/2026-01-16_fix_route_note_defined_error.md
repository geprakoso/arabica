# Fix: Route [filament.admin.resources.absensi.absensis.edit] not defined

**Tanggal:** 16 Januari 2026  
**Error:** `Symfony\Component\Routing\Exception\RouteNotFoundException`  
**Status:** ✅ RESOLVED

---

## 📋 Ringkasan Masalah

### Error Message
```
Route [filament.admin.resources.absensi.absensis.edit] not defined.
```

### Kapan Error Terjadi?
- Error muncul **SETELAH** data absensi berhasil disimpan ke database
- Terjadi saat sistem mencoba menampilkan notifikasi dengan action button "Lihat"
- User sudah berhasil absen, tapi muncul error 500

### Lokasi Error
- **File:** `app/Filament/Resources/Absensi/AbsensiResource/Pages/CreateAbsensi.php`
- **Method:** `afterCreate()`
- **Line:** 178, 195, 210

---

## 🔍 Analisis Penyebab

### 1. Root Cause
Resource `AbsensiResource` **tidak mendefinisikan halaman edit** di method `getPages()`:

```php
// File: AbsensiResource.php
public static function getPages(): array
{
    return [
        'index' => Pages\ListAbsensis::route('/'),
        'create' => Pages\CreateAbsensi::route('/create'),
        // ❌ TIDAK ADA: 'edit' => Pages\EditAbsensi::route('/{record}/edit'),
    ];
}
```

### 2. Mengapa Error Baru Muncul Setelah Insert?
Alur eksekusi:
1. ✅ User submit form absensi
2. ✅ Validasi berhasil
3. ✅ Data berhasil di-insert ke database
4. ✅ Method `afterCreate()` dipanggil
5. ❌ Notifikasi mencoba membuat link ke halaman edit yang tidak ada
6. ❌ Error: Route not found

### 3. Kode Bermasalah

```php
// File: CreateAbsensi.php - Line 172-185
protected function afterCreate(): void
{
    // ...
    
    $notification = Notification::make()
        ->title('Berhasil absen masuk')
        ->body("...")
        ->actions([
            Action::make('Lihat')
                ->url(AbsensiResource::getUrl('edit', ['record' => $this->record])), // ❌ ERROR!
        ])
        ->success();
}
```

Ketika `AbsensiResource::getUrl('edit', ...)` dipanggil:
- Filament mencari route bernama `filament.admin.resources.absensi.absensis.edit`
- Route tersebut **tidak terdaftar** karena tidak ada di `getPages()`
- Exception thrown: `RouteNotFoundException`

---

## ✅ Solusi yang Diterapkan

### Opsi Dipilih: Hapus Action Button (Tanpa Halaman Edit)

Karena resource ini tidak memerlukan halaman edit (absensi hanya create & view), maka:
- ✅ Hapus semua action button "Lihat" dari notifikasi
- ✅ Tambahkan `->send()` untuk toast notification
- ✅ Tetap simpan notifikasi ke database

---

## 🛠️ Implementasi Fix

### File yang Diubah
`app/Filament/Resources/Absensi/AbsensiResource/Pages/CreateAbsensi.php`

### Perubahan 1: Notifikasi Status "Hadir"

**SEBELUM:**
```php
$notification = Notification::make()
    ->title('Berhasil absen masuk')
    ->body("Anda telah absen masuk pada {$this->record->tanggal->format('d-m-Y')} pukul {$this->record->jam_masuk}.")
    ->icon('heroicon-o-check-circle')
    ->actions([
        Action::make('Lihat')
            ->url(AbsensiResource::getUrl('edit', ['record' => $this->record])), // ❌ ERROR
    ])
    ->success();

$notification->send();
$notification->sendToDatabase($user);
```

**SESUDAH:**
```php
$notification = Notification::make()
    ->title('Berhasil absen masuk')
    ->body("Anda telah absen masuk pada {$this->record->tanggal->format('d-m-Y')} pukul {$this->record->jam_masuk}.")
    ->icon('heroicon-o-check-circle')
    ->success(); // ✅ Hapus actions

$notification->send(); // ✅ Toast notification
$notification->sendToDatabase($user); // ✅ Database notification
```

### Perubahan 2: Notifikasi Status "Izin/Sakit"

**SEBELUM:**
```php
$notification = Notification::make()
    ->title('Pengajuan absensi tersimpan')
    ->body("Status: {$status} pada {$this->record->tanggal -> format('d-m-Y')}.")
    ->icon('heroicon-o-document-check')
    ->actions([
        Action::make('Lihat')
            ->url(AbsensiResource::getUrl('edit', ['record' => $this->record])), // ❌ ERROR
    ]);

$notification->sendToDatabase($user);
```

**SESUDAH:**
```php
$notification = Notification::make()
    ->title('Pengajuan absensi tersimpan')
    ->body("Status: {$status} pada {$this->record->tanggal->format('d-m-Y')}.")
    ->icon('heroicon-o-document-check')
    ->info(); // ✅ Hapus actions, tambah color

$notification->send(); // ✅ Toast notification
$notification->sendToDatabase($user); // ✅ Database notification
```

### Perubahan 3: Notifikasi Fallback

**SEBELUM:**
```php
$notification = Notification::make()
    ->title('Absensi baru dibuat')
    ->body("Status: {$status} pada {$this->record->tanggal}.")
    ->icon('heroicon-o-check-circle')
    ->actions([
        Action::make('Lihat')
            ->url(AbsensiResource::getUrl('edit', ['record' => $this->record])), // ❌ ERROR
    ]);

$notification->sendToDatabase($user);
```

**SESUDAH:**
```php
$notification = Notification::make()
    ->title('Absensi baru dibuat')
    ->body("Status: {$status} pada {$this->record->tanggal}.")
    ->icon('heroicon-o-check-circle')
    ->success(); // ✅ Hapus actions

$notification->send(); // ✅ Toast notification
$notification->sendToDatabase($user); // ✅ Database notification
```

---

## 📝 Code Lengkap Setelah Fix

```php
<?php

namespace App\Filament\Resources\Absensi\AbsensiResource\Pages;

use App\Models\Absensi;
use App\Models\ProfilePerusahaan;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Absensi\AbsensiResource;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;
    protected ?string $heading = '';

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // ... (kode validasi tidak berubah)
        
        return $data;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        // ... (kode tidak berubah)
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $status = $this->record->status;

        // ✅ NOTIFIKASI HADIR
        if ($status === 'hadir') {
            $notification = Notification::make()
                ->title('Berhasil absen masuk')
                ->body("Anda telah absen masuk pada {$this->record->tanggal->format('d-m-Y')} pukul {$this->record->jam_masuk}.")
                ->icon('heroicon-o-check-circle')
                ->success();
            
            $notification->send();
            $notification->sendToDatabase($user);

            return;
        }

        // ✅ NOTIFIKASI IZIN/SAKIT
        if (in_array($status, ['izin', 'sakit'], true)) {
            $notification = Notification::make()
                ->title('Pengajuan absensi tersimpan')
                ->body("Status: {$status} pada {$this->record->tanggal->format('d-m-Y')}.")
                ->icon('heroicon-o-document-check')
                ->info();

            $notification->send();
            $notification->sendToDatabase($user);

            return;
        }

        // ✅ FALLBACK
        $notification = Notification::make()
            ->title('Absensi baru dibuat')
            ->body("Status: {$status} pada {$this->record->tanggal}.")
            ->icon('heroicon-o-check-circle')
            ->success();

        $notification->send();
        $notification->sendToDatabase($user);
    }
}
```

---

## 🎯 Hasil Setelah Fix

### Sebelum Fix
- ❌ Error 500 setelah absen berhasil
- ❌ User bingung (data tersimpan tapi error)
- ❌ Notifikasi tidak muncul

### Setelah Fix
- ✅ Absensi berhasil tanpa error
- ✅ Toast notification muncul di layar (hijau/info)
- ✅ Database notification tersimpan (bell icon)
- ✅ Auto redirect ke halaman index
- ✅ User experience lebih baik

---

## 🔄 Alternatif Solusi (Tidak Digunakan)

### Opsi 2: Tambahkan Halaman Edit

Jika di masa depan ingin menambahkan fitur edit absensi:

**1. Buat file baru:**
```bash
touch app/Filament/Resources/Absensi/AbsensiResource/Pages/EditAbsensi.php
```

**2. Isi file `EditAbsensi.php`:**
```php
<?php

namespace App\Filament\Resources\Absensi\AbsensiResource\Pages;

use App\Filament\Resources\Absensi\AbsensiResource;
use Filament\Resources\Pages\EditRecord;

class EditAbsensi extends EditRecord
{
    protected static string $resource = AbsensiResource::class;
}
```

**3. Update `AbsensiResource.php`:**
```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListAbsensis::route('/'),
        'create' => Pages\CreateAbsensi::route('/create'),
        'edit' => Pages\EditAbsensi::route('/{record}/edit'), // ✅ TAMBAHKAN
    ];
}
```

**4. Kembalikan action button di notifikasi:**
```php
->actions([
    Action::make('Lihat')
        ->url(AbsensiResource::getUrl('edit', ['record' => $this->record])),
])
```

---

## 📚 Pelajaran yang Didapat

### 1. Filament Route Naming Convention
Filament membuat route name otomatis dengan format:
```
filament.{panel}.resources.{resource_path}.{page_name}
```

Contoh:
- Panel: `admin`
- Resource: `absensi/absensis` (plural dari Absensi)
- Page: `edit`
- Route name: `filament.admin.resources.absensi.absensis.edit`

### 2. Method `getUrl()` di Resource
```php
// Memanggil route yang terdaftar di getPages()
AbsensiResource::getUrl('index'); // ✅ OK
AbsensiResource::getUrl('create'); // ✅ OK
AbsensiResource::getUrl('edit', ['record' => $record]); // ❌ ERROR (tidak terdaftar)
```

### 3. Notification Best Practices
```php
$notification = Notification::make()
    ->title('...')
    ->body('...')
    ->success(); // atau ->info(), ->warning(), ->danger()

// Toast notification (pop-up di layar)
$notification->send();

// Database notification (bell icon)
$notification->sendToDatabase($user);
```

---

## 🧪 Testing Checklist

- [x] Absen dengan status "Hadir" → ✅ Berhasil tanpa error
- [x] Absen dengan status "Izin" → ✅ Berhasil tanpa error
- [x] Absen dengan status "Sakit" → ✅ Berhasil tanpa error
- [x] Toast notification muncul → ✅ Muncul
- [x] Database notification tersimpan → ✅ Tersimpan
- [x] Data tersimpan di database → ✅ Tersimpan
- [x] Redirect ke index → ✅ Berhasil

---

## 📌 Catatan Tambahan

### Mengapa Error Baru Ketahuan Sekarang?
Kemungkinan:
1. Fitur notifikasi baru ditambahkan
2. Sebelumnya tidak ada action button "Lihat"
3. Testing tidak cover skenario ini

### Prevention untuk Masa Depan
1. ✅ Selalu cek `getPages()` sebelum menggunakan `getUrl()`
2. ✅ Gunakan try-catch untuk route generation
3. ✅ Test semua notification flow
4. ✅ Dokumentasikan semua route yang tersedia

---

## 🔗 Referensi

- [Filament Notifications Documentation](https://filamentphp.com/docs/3.x/notifications/sending-notifications)
- [Filament Resources Documentation](https://filamentphp.com/docs/3.x/panels/resources/getting-started)
- [Laravel Routing Documentation](https://laravel.com/docs/11.x/routing)

---

**Dibuat oleh:** Antigravity AI  
**Tanggal:** 16 Januari 2026  
**Versi Laravel:** 12.46.0  
**Versi Filament:** 3.x  
**Status:** ✅ Production Ready
