# SN (Serial Number) Search Feature

Dokumentasi fitur pencarian Serial Number (SN) pada tabel list resource.

## Deskripsi

Fitur ini memungkinkan user untuk mencari transaksi berdasarkan Serial Number tanpa perlu menampilkan kolom SN di tabel.

## Resources yang Sudah Implement

| Resource | File | Table Column |
|----------|------|--------------|
| PembelianResource | `app/Filament/Resources/PembelianResource.php` | `items_serials` |
| PenjualanResource | `app/Filament/Resources/PenjualanResource.php` | `items_serials` |

## Implementasi

### Column Definition

```php
TextColumn::make('items_serials')
    ->label('SN')
    ->toggleable(isToggledHiddenByDefault: true)
    ->state(function (Model $record): string {
        $allSerials = $record->items
            ->flatMap(fn ($item) => collect($item->serials ?? [])->pluck('sn'))
            ->filter()
            ->values();

        if ($allSerials->isEmpty()) {
            return '-';
        }

        return $allSerials->implode(', ');
    })
    ->wrap()
    ->limit(30)
    ->tooltip(function (Model $record): ?string {
        $allSerials = $record->items
            ->flatMap(fn ($item) => collect($item->serials ?? [])->pluck('sn'))
            ->filter()
            ->values();

        return $allSerials->count() > 0 ? $allSerials->implode(', ') : null;
    })
    ->searchable(query: function (Builder $query, string $search): Builder {
        return $query->whereHas('items', function (Builder $q) use ($search): void {
            $q->whereRaw("JSON_SEARCH(serials, 'one', ?, NULL, '$[*].sn') IS NOT NULL", ["%{$search}%"]);
        });
    }),
```

### Key Points

1. **Hidden by Default**: `toggleable(isToggledHiddenByDefault: true)`
   - Kolom tidak ditampilkan secara default
   - User bisa toggle dari column settings jika ingin melihat

2. **State Function**: Mengambil semua SN dari items
   - `flatMap` untuk flatten nested serials
   - `pluck('sn')` untuk ambil value SN saja
   - `filter()` untuk hapus null/empty
   - `implode(', ')` untuk gabung jadi string

3. **Tooltip**: Menampilkan semua SN saat hover

4. **JSON Search Query**:
   ```sql
   JSON_SEARCH(serials, 'one', ?, NULL, '$[*].sn') IS NOT NULL
   ```
   - `serials` = nama kolom JSON di tabel items
   - `'one'` = cari satu hasil saja (lebih cepat)
   - `$[*].sn` = path ke field SN dalam array JSON

## Struktur Data Serials

Field `serials` di tabel items menyimpan array JSON:

```json
[
    {"sn": "ABC123", "garansi": "1 Tahun"},
    {"sn": "DEF456", "garansi": "6 Bulan"}
]
```

## Penggunaan

1. Buka halaman **List Pembelian** atau **List Penjualan**
2. Ketik serial number di kolom search
3. Hasil akan menampilkan transaksi yang memiliki SN tersebut
4. Optional: Toggle kolom SN dari column settings untuk melihat SN di tabel

## Catatan

- Fitur ini **tidak menambah beban query** karena kolom hidden by default
- Search menggunakan **parameterized query** untuk keamanan SQL injection
- Query `JSON_SEARCH` kompatibel dengan MySQL 5.7+ dan MariaDB 10.2+
