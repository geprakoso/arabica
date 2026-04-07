# Inventory - Download PDF

Dokumentasi fitur download PDF untuk laporan inventory (stok opname).

## Deskripsi

Fitur ini tersedia pada menu **Inventori → Stock Ready** dan menghasilkan laporan stok opname dalam format PDF. Data dapat difilter berdasarkan brand dan kategori sebelum diekspor.

## Lokasi File

| File | Keterangan |
| --- | --- |
| `app/Filament/Resources/InventoryResource.php` | Definisi aksi header untuk export PDF inventory |

## Plugin

Fitur export menggunakan plugin `AlperenErsoy/FilamentExport` melalui `FilamentExportHeaderAction`.

## Cara Menggunakan

1. Buka menu **Inventori → Stock Ready**.
2. (Opsional) Gunakan filter **Brand** atau **Kategori** sesuai kebutuhan.
3. Klik tombol **Download** di bagian atas tabel.
4. File PDF otomatis terunduh dengan nama `Stok Opname _ <tanggal>`.

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
