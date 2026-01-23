# Upload Foto Dokumentasi - Pembelian

Dokumentasi fitur upload foto dokumentasi pada halaman view Pembelian.

## Deskripsi

Fitur ini memungkinkan user untuk mengupload foto dokumentasi (nota, invoice, bukti penerimaan barang) langsung dari halaman view Pembelian tanpa perlu masuk ke halaman edit.

## Lokasi File

| File | Keterangan |
|------|------------|
| `app/Filament/Resources/PembelianResource/Pages/ViewPembelian.php` | Header action "Upload Foto" |
| `app/Filament/Resources/PembelianResource.php` | Infolist section "Bukti & Dokumentasi" |
| `resources/views/filament/infolists/components/pembelian-photos-gallery.blade.php` | Blade view gallery |
| `app/Models/Pembelian.php` | Model dengan field `foto_dokumen` |
| `database/migrations/2026_01_22_174938_add_foto_dokumen_to_tb_pembelian_table.php` | Migration |

## Database

### Field
- **Nama**: `foto_dokumen`
- **Tipe**: JSON (array of strings)
- **Tabel**: `tb_pembelian`

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

### Accepted File Types
```php
->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
```

### Storage
- **Disk**: `public`
- **Directory**: `pembelian/dokumentasi`

## Implementasi Header Action

```php
Action::make('upload_dokumen')
    ->label('Upload Foto')
    ->icon('heroicon-m-camera')
    ->color('success')
    ->modalHeading('Upload Foto Dokumentasi')
    ->modalDescription('Upload foto nota, invoice, atau bukti penerimaan barang.')
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
        $existingPhotos = $this->record->foto_dokumen ?? [];
        $newPhotos = $data['foto'] ?? [];
        
        // Merge existing photos with new ones
        $allPhotos = array_merge($existingPhotos, $newPhotos);
        
        $this->record->update([
            'foto_dokumen' => $allPhotos,
        ]);
    })
```

## Gallery Display (Infolist)

### Section Configuration
```php
InfoSection::make('Bukti & Dokumentasi')
    ->icon('heroicon-o-camera')
    ->visible(fn (Pembelian $record) => 
        $record->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty() 
        || ! empty($record->foto_dokumen)
    )
    ->schema([
        ViewEntry::make('all_photos_gallery')
            ->hiddenLabel()
            ->view('filament.infolists.components.pembelian-photos-gallery')
            ->state(fn (Pembelian $record) => [
                'bukti_pembayaran' => $record->pembayaran->whereNotNull('bukti_transfer')->pluck('bukti_transfer')->toArray(),
                'foto_dokumen' => $record->foto_dokumen ?? [],
            ]),
    ])
```

### Blade View Style (1:1 Grid)
```blade
<div class="flex flex-wrap gap-3">
    @forelse($allPhotos as $index => $foto)
        <a href="{{ Storage::url($foto) }}" target="_blank">
            <img 
                src="{{ Storage::url($foto) }}" 
                alt="Foto {{ $index + 1 }}"
                class="rounded-md shadow-sm border border-gray-200 dark:border-gray-700 object-cover cursor-pointer hover:shadow-md transition-shadow"
                style="width: 100px; height: 100px; aspect-ratio: 1/1;"
            />
        </a>
    @empty
        <p class="text-gray-500">Tidak ada foto</p>
    @endforelse
</div>
```

## Penggunaan

1. Buka halaman **View Pembelian**
2. Klik tombol hijau **"Upload Foto"** di header
3. Modal akan terbuka dengan opsi:
   - Upload satu atau banyak foto sekaligus
   - Reorder foto yang sudah dipilih
4. Klik **Submit** untuk menyimpan
5. Foto akan muncul di section **"Bukti & Dokumentasi"**

## Catatan

- Foto baru akan **merged** dengan foto yang sudah ada (tidak menimpa)
- Section "Bukti & Dokumentasi" menampilkan gabungan:
  - Bukti pembayaran dari relasi `pembayaran.bukti_transfer`
  - Foto dokumentasi dari field `foto_dokumen`
- Semua foto ditampilkan dalam grid 1:1 (100x100 pixel)

---

## Implementasi di PenjualanResource

Fitur yang sama juga tersedia di PenjualanResource:

| File | Keterangan |
|------|------------|
| `app/Filament/Resources/PenjualanResource/Pages/ViewPenjualan.php` | Header action "Upload Foto" |
| `app/Filament/Resources/PenjualanResource.php` | Infolist section "Bukti & Dokumentasi" |
| `resources/views/filament/infolists/components/penjualan-photos-gallery.blade.php` | Blade view gallery |
| `app/Models/Penjualan.php` | Model dengan field `foto_dokumen` |
| `database/migrations/2026_01_22_181035_add_foto_dokumen_to_tb_penjualan_table.php` | Migration |

### Storage Directory
- **Penjualan**: `penjualan/dokumentasi`
- **Pembelian**: `pembelian/dokumentasi`

