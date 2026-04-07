# Upload Foto Dokumentasi - Request Order

Dokumentasi fitur upload foto dokumentasi pada halaman view Request Order.

## Deskripsi

Fitur ini memungkinkan user untuk mengupload foto dokumentasi (nota, invoice, bukti penerimaan barang, atau lampiran lainnya) langsung dari halaman view Request Order tanpa masuk halaman edit.

## Lokasi File

| File | Keterangan |
|------|------------|
| `app/Filament/Resources/RequestOrderResource/Pages/ViewRequestOrder.php` | Header action "Upload Foto" |
| `app/Filament/Resources/RequestOrderResource.php` | Infolist section "Foto Dokumentasi" |
| `resources/views/filament/infolists/components/foto-dokumen-gallery.blade.php` | Blade view gallery |
| `app/Models/RequestOrder.php` | Model dengan field `foto_dokumen` |
| `database/migrations/2026_01_23_160000_add_foto_dokumen_to_request_orders_table.php` | Migration |

## Database

### Field
- **Nama**: `foto_dokumen`
- **Tipe**: JSON (array of strings)
- **Tabel**: `request_orders`

### Model Cast
```php
protected $casts = [
    'foto_dokumen' => 'array',
];
```

## Konfigurasi Upload

### WebP Compression
```php
->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
    return WebpUpload::store($component, $file, 80);
})
```
- Konversi otomatis ke format WebP
- Kualitas 80%
- Menggunakan helper `App\Support\WebpUpload`

### Image Resize
```php
->imageResizeMode('contain')
->imageResizeTargetWidth('1920')
->imageResizeTargetHeight('1080')
```
- Mode: contain (menjaga aspect ratio)
- Maksimal: 1920x1080 pixel

### Preview Layout
```php
->panelLayout('grid')
->panelAspectRatio('1:1')
->imagePreviewHeight('100')
```
- Tampilan preview dirapikan jadi grid
- Ukuran thumbnail 100x100 pixel

### Accepted File Types
```php
->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
```

### Storage
- **Disk**: `public`
- **Directory**: `request-order/dokumentasi`

## Implementasi Header Action

```php
Action::make('upload_dokumen')
    ->label('Upload Foto')
    ->icon('heroicon-m-camera')
    ->color('success')
    ->modalHeading('Upload Foto Dokumentasi')
    ->modalDescription('Upload foto dokumentasi request order.')
    ->modalWidth('md')
    ->form([
        FileUpload::make('foto')
            ->label('Foto Dokumentasi')
            ->image()
            ->multiple()
            ->reorderable()
            // ... konfigurasi lainnya
    ])
    ->action(function (array $data): void {
        $this->record->update([
            'foto_dokumen' => $data['foto'] ?? [],
        ]);
    })
```

## Gallery Display (Infolist)

### Section Configuration
```php
InfoSection::make('Foto Dokumentasi')
    ->icon('heroicon-o-camera')
    ->visible(fn (RequestOrder $record) => ! empty($record->foto_dokumen))
    ->schema([
        ViewEntry::make('foto_dokumen')
            ->hiddenLabel()
            ->view('filament.infolists.components.foto-dokumen-gallery')
            ->state(fn (RequestOrder $record) => $record->foto_dokumen ?? []),
    ])
```

### Blade View Style
```blade
<div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-3">
    @forelse($getState() ?? [] as $index => $foto)
        <a href="{{ Storage::url($foto) }}" target="_blank">
            <img
                src="{{ Storage::url($foto) }}"
                alt="Foto {{ $index + 1 }}"
                class="w-full aspect-square object-cover rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer"
            />
        </a>
    @empty
        <p class="text-gray-500 col-span-full">Tidak ada foto dokumentasi</p>
    @endforelse
</div>
```

## Penggunaan

1. Buka halaman **View Request Order**
2. Klik tombol hijau **"Upload Foto"** di header
3. Modal akan terbuka dengan opsi:
   - Upload satu atau banyak foto sekaligus
   - Reorder foto yang sudah dipilih
4. Klik **Submit** untuk menyimpan
5. Foto akan muncul di section **"Foto Dokumentasi"**

## Catatan

- Foto lama bisa dihapus langsung dari modal (submit akan menyimpan ulang daftar foto)
- Gallery menampilkan daftar foto dari field `foto_dokumen`
- Semua foto ditampilkan dalam grid 1:1 (100x100 pixel)
