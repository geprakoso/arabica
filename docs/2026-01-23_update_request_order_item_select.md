# Update Select Item - Request Order

Dokumentasi perubahan flow pemilihan item pada Request Order.

## Deskripsi

Pemilihan item sekarang menggunakan alur **kategori → produk**, sehingga pilihan produk akan difilter berdasarkan kategori yang dipilih. Field brand dihapus agar form lebih ringkas. Nama produk ditampilkan dalam format **UPPERCASE** untuk konsistensi.

## Lokasi File

| File | Keterangan |
|------|------------|
| `app/Filament/Resources/RequestOrderResource.php` | Update form select kategori dan produk |
| `resources/views/filament/infolists/components/request-order-items-table.blade.php` | Tampilan produk uppercase di detail view |

## Perubahan Form Item

### Flow Pilih Kategori → Produk
```php
Forms\Components\Select::make('kategori_id')
    ->label('Kategori')
    ->options(fn () => \App\Models\Kategori::query()->orderBy('nama_kategori')->pluck('nama_kategori', 'id'))
    ->searchable()
    ->preload()
    ->required()
    ->native(false)
    ->reactive()
    ->afterStateUpdated(fn (callable $set) => $set('produk_id', null))
    ->dehydrated(false)

Forms\Components\Select::make('produk_id')
    ->label('Nama Produk')
    ->options(fn (Get $get) => $get('kategori_id')
        ? \App\Models\Produk::query()
            ->where('kategori_id', $get('kategori_id'))
            ->orderBy('nama_produk')
            ->get()
            ->mapWithKeys(fn ($produk) => [$produk->id => strtoupper($produk->nama_produk)])
            ->all()
        : [])
    ->searchable()
    ->preload()
    ->required()
    ->native(false)
    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
    ->placeholder('Cari Produk...')
    ->disabled(fn (Get $get) => blank($get('kategori_id')))
    ->reactive()
```

### Catatan
- `kategori_id` bersifat **non-persisten** (untuk filtering), yang disimpan tetap `produk_id`.
- Produk hanya muncul setelah kategori dipilih.
- Brand tidak ditampilkan lagi di form item.

## Tampilan Produk (Detail View)

```blade
{{ strtoupper(data_get($item, 'produk.nama_produk') ?? '-') }}
```

## Dampak

- Flow input lebih rapi (kategori dulu, baru produk).
- Konsistensi tampilan nama produk (uppercase) di form dan detail view.
