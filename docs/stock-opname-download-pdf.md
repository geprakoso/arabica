# Stock Opname - Download PDF

Dokumentasi fitur download PDF untuk data inventory (stok opname).

## Deskripsi

Fitur ini tersedia pada menu **Inventori → Stock Opname** dan menghasilkan laporan stok opname berbasis data inventory dalam format PDF.

## Lokasi File

| File | Keterangan |
| --- | --- |
| `app/Filament/Resources/StockOpnameResource/Pages/ListStockOpnames.php` | Aksi header export PDF inventory untuk kebutuhan stok opname |
| `app/Filament/Actions/InventoryExportHeaderAction.php` | Query export inventory khusus untuk halaman stock opname |

## Plugin

Fitur export menggunakan plugin `AlperenErsoy/FilamentExport` melalui `FilamentExportHeaderAction`.

## Cara Menggunakan

1. Buka menu **Inventori → Stock Opname**.
2. Klik tombol **Download** di bagian atas tabel.
3. File PDF otomatis terunduh dengan nama `Stok Opname _ <tanggal>`.

## Kolom yang Diekspor

- SKU
- Nama Produk
- Brand
- Kategori
- Stok Sistem
- HPP Terkini
- Harga Jual Terkini
- Stok Opname
- Selisih

## Catatan

- Laporan diurutkan dan dikelompokkan berdasarkan kategori.
- Field **Stok Opname** dan **Selisih** disediakan kosong untuk kebutuhan audit manual.
