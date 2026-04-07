# Implementasi Modal Serial Number pada Tukar Tambah

**Tanggal**: 15 Januari 2026  
**Resource**: `TukarTambahResource.php`  
**Tujuan**: Mengubah input serial number dari tampilan inline menjadi modal popup untuk UI yang lebih bersih dan user-friendly

## Ringkasan Perubahan

Perubahan ini mengimplementasikan sistem modal popup untuk manajemen serial number pada form Tukar Tambah, khususnya di bagian "Daftar Barang Keluar" (Penjualan). Sebelumnya, serial number ditampilkan sebagai nested table repeater yang memakan banyak ruang. Sekarang diganti dengan tombol yang menampilkan jumlah serial dan membuka modal untuk pengelolaan detail.

## Detail Implementasi

### 1. Struktur Komponen

#### Komponen Utama
- **Hidden Field (`serials`)**: Menyimpan data serial number aktual dalam format array
- **TextInput (`serials_count`)**: Menampilkan jumlah serial number (disabled, tidak disimpan ke database)
- **Suffix Action (`manage_serials`)**: Tombol dengan ikon QR code dan label "Manage" yang membuka modal

#### Modal Components
- **Modal Heading**: "Manage Serial Numbers"
- **Modal Width**: `2xl` (extra large)
- **Repeater (`serials_temp`)**: Form input temporary untuk mengelola serial number
  - Field `sn`: Serial Number (required)
  - Field `garansi`: Informasi garansi (optional)

### 2. Alur Data

```
┌─────────────────────────────────────────────────────────────┐
│  Main Form (TableRepeater items)                            │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Hidden Field: serials                               │    │
│  │ - Menyimpan data aktual: [{sn, garansi}, ...]     │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │ TextInput: serials_count (disabled)                │    │
│  │ - Display: "X serials"                             │    │
│  │ - Reactive dengan ->live()                         │    │
│  │                                                     │    │
│  │  [QR Icon] Manage  ← Suffix Action Button         │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  Klik "Manage" ↓                                            │
│                                                              │
│  ┌────────────────────────────────────────────────────┐    │
│  │ MODAL: Manage Serial Numbers                       │    │
│  │                                                     │    │
│  │  Repeater: serials_temp                            │    │
│  │  ┌─────────────────────────────────────────┐      │    │
│  │  │ Serial Number: [____] Garansi: [____]   │      │    │
│  │  │ Serial Number: [____] Garansi: [____]   │      │    │
│  │  └─────────────────────────────────────────┘      │    │
│  │                                                     │    │
│  │  [+ Add Serial]  [Kirim]  [Batal]                 │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  Klik "Kirim" ↓                                             │
│  Data dari serials_temp → disalin ke serials (hidden)      │
└─────────────────────────────────────────────────────────────┘
```

### 3. Kode Implementasi

#### Import yang Diperlukan
```php
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
```

#### Struktur Field
```php
// Hidden field untuk menyimpan data aktual
Hidden::make('serials')
    ->default([]),

// TextInput untuk menampilkan count + tombol manage
TextInput::make('serials_count')
    ->label('Serial Number & Garansi')
    ->formatStateUsing(fn (Get $get): string => count($get('serials') ?? []) . ' serials')
    ->live()  // Membuat field reactive
    ->disabled()
    ->dehydrated(false)  // Tidak disimpan ke database
    ->suffixAction(
        FormAction::make('manage_serials')
            ->label('Manage')
            ->icon('heroicon-o-qr-code')
            ->button()  // Menampilkan sebagai button penuh (icon + label)
            ->color('info')
            ->modalHeading('Manage Serial Numbers')
            ->modalWidth('2xl')
            ->fillForm(fn (Get $get): array => [
                'serials_temp' => $get('serials') ?? [],
            ])
            ->form([
                Repeater::make('serials_temp')
                    ->label('Serial Numbers')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('sn')
                                    ->label('Serial Number')
                                    ->required(),
                                TextInput::make('garansi')
                                    ->label('Garansi'),
                            ]),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('+ Add Serial')
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['sn'] ?? 'New Serial'),
            ])
            ->action(function (Set $set, array $data): void {
                $set('serials', $data['serials_temp'] ?? []);
            })
    ),
```

### 4. Fitur-Fitur Khusus

#### A. Reactive Count Display
- Menggunakan `->live()` untuk membuat field reactive
- `->formatStateUsing()` menghitung dan menampilkan jumlah serial
- **Catatan**: Saat ini count tidak update secara real-time setelah modal disimpan (known issue)

#### B. Button dengan Icon dan Label
- Menggunakan `->button()` untuk menampilkan button penuh
- Tanpa `->button()`, hanya icon yang muncul (label jadi tooltip)
- Display: `[QR Icon] Manage`

#### C. Data Transfer
- **fillForm**: Mengisi modal dengan data dari `serials` hidden field
- **action**: Menyalin data dari `serials_temp` kembali ke `serials` saat modal disimpan

#### D. Repeater Features
- **Collapsible**: Item bisa di-collapse untuk menghemat ruang
- **Item Label**: Menampilkan serial number sebagai label item
- **No Reorder**: Urutan tidak bisa diubah (sesuai urutan input)

### 5. Integrasi dengan TableRepeater

Field ini berada di dalam `TableRepeater::make('items')` dengan konfigurasi:

```php
->colStyles([
    'id_produk' => 'width: 30%;',
    'kondisi' => 'width: 10%;',
    'qty' => 'width: 10%;',
    'harga_jual' => 'width: 15%;',
    // serials_count mengambil sisa ruang
])
```

## Known Issues & Limitations

### 1. Serial Count Tidak Update Reactive (Deferred)
**Masalah**: Setelah menambah/menghapus serial di modal dan klik "Kirim", angka count (e.g., "0 serials") tidak langsung update. Baru update setelah refresh halaman.

**Penyebab**: `formatStateUsing()` hanya evaluate sekali saat field pertama kali di-render. Meskipun sudah ditambahkan `->live()`, Filament tidak otomatis re-evaluate `formatStateUsing()` saat `serials` hidden field berubah melalui custom action.

**Solusi Potensial** (untuk masa depan):
- **Opsi A**: Gunakan `Placeholder` dengan `->content()` yang reactive + custom CSS untuk align button
- **Opsi B**: Custom Livewire component untuk observe perubahan `serials` field
- **Opsi C**: Dispatch Livewire event untuk trigger refresh

**Status**: Ditunda untuk perbaikan di masa depan. Fungsionalitas inti (penyimpanan data) bekerja dengan baik.

### 2. Button Styling
- Suffix action button menggunakan warna `info` (biru)
- Posisi button di dalam input field (sebagai suffix)
- Tidak bisa di-align ke kiri karena nature dari `suffixAction`

## Testing Checklist

- [x] Modal terbuka saat klik tombol "Manage"
- [x] Modal menampilkan data serial yang sudah tersimpan
- [x] Bisa menambah serial baru di modal
- [x] Bisa mengedit serial yang ada
- [x] Bisa menghapus serial
- [x] Data tersimpan setelah klik "Kirim"
- [x] Data persisten setelah save form utama
- [x] Data muncul kembali saat re-open modal
- [x] Button menampilkan icon dan label "Manage"
- [ ] Count update reactive (known issue - deferred)

## Referensi

- **File**: `/home/galih/arabica/app/Filament/Resources/TukarTambahResource.php`
- **Lines**: 286-328 (implementasi serial number modal)
- **Filament Docs**: 
  - [Form Actions](https://filamentphp.com/docs/3.x/forms/actions)
  - [Repeater](https://filamentphp.com/docs/3.x/forms/fields/repeater)
  - [Table Repeater Plugin](https://github.com/Icetalker/filament-table-repeater)

## Changelog Entry

Lihat `changelog.md` bagian `2026.01.15` untuk ringkasan perubahan yang user-facing.
