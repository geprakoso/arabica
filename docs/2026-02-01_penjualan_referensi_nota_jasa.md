# Penjualan - Referensi Nota Jasa

Dokumentasi ini menjelaskan sinkronisasi referensi nota untuk jasa di Penjualan.
Fokusnya: Jasa memakai referensi dari Pembelian Jasa, bukan Pembelian Item barang.

## Tujuan
- Referensi nota jasa di Penjualan mengarah ke `tb_pembelian_jasa`.
- Tetap kompatibel dengan data lama yang masih memakai `pembelian_item_id`.

## Struktur Migrasi
1) Tambah kolom `pembelian_jasa_id` pada `tb_penjualan_jasa`
2) Backfill data lama agar `pembelian_jasa_id` terisi bila memungkinkan

### 1) Tambah Kolom
File: `database/migrations/2026_02_01_000000_add_pembelian_jasa_id_to_penjualan_jasa_table.php`

```php
Schema::table('tb_penjualan_jasa', function (Blueprint $table): void {
    $table->foreignId('pembelian_jasa_id')
        ->nullable()
        ->after('pembelian_item_id')
        ->constrained('tb_pembelian_jasa', 'id_pembelian_jasa')
        ->nullOnDelete();
});
```

### 2) Backfill Data Lama
File: `database/migrations/2026_02_01_000100_backfill_pembelian_jasa_id_on_penjualan_jasa_table.php`

```php
DB::table('tb_penjualan_jasa')
    ->join('tb_pembelian_item', 'tb_penjualan_jasa.pembelian_item_id', '=', 'tb_pembelian_item.id_pembelian_item')
    ->whereNull('tb_penjualan_jasa.pembelian_jasa_id')
    ->whereNotNull('tb_penjualan_jasa.pembelian_item_id')
    ->select(
        'tb_penjualan_jasa.id_penjualan_jasa',
        'tb_penjualan_jasa.jasa_id',
        'tb_pembelian_item.id_pembelian',
    )
    ->orderBy('tb_penjualan_jasa.id_penjualan_jasa')
    ->chunkById(200, function ($rows): void {
        foreach ($rows as $row) {
            $pembelianJasaId = DB::table('tb_pembelian_jasa')
                ->where('id_pembelian', $row->id_pembelian)
                ->where('jasa_id', $row->jasa_id)
                ->orderBy('id_pembelian_jasa')
                ->value('id_pembelian_jasa');

            if (! $pembelianJasaId) {
                continue;
            }

            DB::table('tb_penjualan_jasa')
                ->where('id_penjualan_jasa', $row->id_penjualan_jasa)
                ->whereNull('pembelian_jasa_id')
                ->update(['pembelian_jasa_id' => $pembelianJasaId]);
        }
    }, 'id_penjualan_jasa');
```

Catatan: Jika terdapat beberapa `tb_pembelian_jasa` untuk jasa yang sama pada pembelian yang sama,
backfill akan mengambil ID paling kecil (pertama).

## Cuplikan Kode Utama

### Model
File: `app/Models/PenjualanJasa.php`

```php
protected $fillable = [
    'id_penjualan',
    'jasa_id',
    'pembelian_item_id',
    'pembelian_jasa_id',
    'qty',
    'harga',
    'catatan',
];

public function pembelianJasa()
{
    return $this->belongsTo(PembelianJasa::class, 'pembelian_jasa_id', 'id_pembelian_jasa');
}
```

### Form Penjualan (Daftar Jasa)
File: `app/Filament/Resources/PenjualanResource.php`

```php
Select::make('pembelian_jasa_id')
    ->label('Referensi Nota')
    ->relationship('pembelianJasa', 'id_pembelian_jasa', fn (Builder $query) => $query->with(['pembelian', 'jasa']))
    ->getOptionLabelFromRecordUsing(function ($record) {
        $nota = $record->pembelian->no_po ?? $record->pembelian->nota_supplier ?? 'No Nota';
        $jasa = $record->jasa->nama_jasa ?? 'Jasa';
        return "{$nota} - {$jasa}";
    })
    ->searchable(['id_pembelian_jasa', 'id_pembelian']);
```

### Infolist Jasa (Detail Penjualan)
File: `resources/views/filament/infolists/components/penjualan-jasa-table.blade.php`

```php
$pembelianJasa = data_get($item, 'pembelianJasa');
$pembelian = data_get($pembelianJasa, 'pembelian');
$jasaNama = data_get($pembelianJasa, 'jasa.nama_jasa');
$nota = data_get($pembelian, 'no_po') ?? data_get($pembelian, 'nota_supplier');

// Fallback untuk data lama:
$pembelianItem = $pembelianItem ?? data_get($item, 'pembelianItem');
$pembelian = $pembelian ?? data_get($pembelianItem, 'pembelian');
$pItemProduk = data_get($pembelianItem, 'produk');
$nota = $nota ?? data_get($pembelian, 'no_po') ?? data_get($pembelian, 'nota_supplier');
$pNama = $jasaNama ?? data_get($pItemProduk, 'nama_produk');
```

## Cara Jalanin
Jalankan migrasi:
```
php artisan migrate
```

Setelah migrasi:
- Referensi nota jasa di Penjualan akan memakai `pembelian_jasa_id`.
- Data lama tetap tampil karena ada fallback.
