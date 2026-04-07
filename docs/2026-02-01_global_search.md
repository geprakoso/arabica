# Global Search

Dokumentasi ringkas perilaku global search Filament dan status resource yang belum mendefinisikan atribut pencarian eksplisit.

## Ringkas Perilaku
- Global search default Filament mencari record dari semua Resource yang bisa di-search.
- Untuk mengatur kolom yang dicari, Resource dapat override:
  - `getGloballySearchableAttributes()`
  - `getGlobalSearchResultTitle()`
  - `getGlobalSearchResultDetails()`

## Provider (Navigation + Records)
Project ini memakai provider custom untuk menggabungkan:
- hasil record (default)
- hasil dari Navigation (label menu)

File: `app/Filament/GlobalSearch/NavigationGlobalSearchProvider.php`

Inti alurnya:
```php
$builder = (new DefaultGlobalSearchProvider())->getResults($query) ?? GlobalSearchResults::make();
// tambah hasil navigation jika label cocok
$builder->category('Navigation', collect($navResults));
```

## Resource Dengan Atribut Pencarian Eksplisit
Resource berikut sudah punya `getGloballySearchableAttributes()`:
- `app/Filament/Resources/Penjadwalan/PenjadwalanPengirimanResource.php`
- `app/Filament/Resources/Penjadwalan/Service/ListGameResource.php`
- `app/Filament/Resources/PembelianResource.php`
- `app/Filament/Resources/Penjadwalan/Service/ListAplikasiResource.php`
- `app/Filament/Resources/PenjualanResource.php`
- `app/Filament/Resources/Akunting/InputTransaksiTokoResource.php`
- `app/Filament/Resources/Penjadwalan/Service/ListOsResource.php`
- `app/Filament/Resources/Akunting/KodeAkunResource.php`
- `app/Filament/Resources/Absensi/AbsensiResource.php`
- `app/Filament/Resources/Absensi/LiburCutiResource.php`
- `app/Filament/Resources/Penjadwalan/PenjadwalanServiceResource.php`
- `app/Filament/Resources/Absensi/LemburResource.php`
- `app/Filament/Resources/MasterData/BrandResource.php`
- `app/Filament/Resources/Akunting/JenisAkunResource.php`
- `app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php`
- `app/Filament/Resources/Penjadwalan/KalenderEventResource.php`
- `app/Filament/Resources/Penjadwalan/Service/CrosscheckResource.php`
- `app/Filament/Resources/MasterData/MemberResource.php`
- `app/Filament/Resources/MasterData/AkunTransaksiResource.php`
- `app/Filament/Resources/MasterData/ProdukResource.php`
- `app/Filament/Resources/MasterData/SupplierResource.php`
- `app/Filament/Resources/MasterData/JasaResource.php`
- `app/Filament/Resources/MasterData/GudangResource.php`
- `app/Filament/Resources/MasterData/KategoriResource.php`
- `app/Filament/Resources/InventoryResource.php`
- `app/Filament/Resources/StockOpnameResource.php`
- `app/Filament/Resources/StockAdjustmentResource.php`
- `app/Filament/Resources/RequestOrderResource.php`
- `app/Filament/Resources/UserResource.php`
- `app/Filament/Resources/TukarTambahResource.php`

## Resource Belum Eksplisit
Resource berikut belum mendefinisikan `getGloballySearchableAttributes()`:
- `app/Filament/Resources/PenjualanReportResource.php`
- `app/Filament/Resources/PosSaleResource.php`
- `app/Filament/Resources/NotificationResource.php`
- `app/Filament/Resources/LaporanPengajuanCutiResource.php`
- `app/Filament/Resources/StockOpnameResource.php` (sudah diisi)
- `app/Filament/Resources/PosActivityResource.php`
- `app/Filament/Resources/PembelianReportResource.php`
- `app/Filament/Resources/Absensi/LaporanAbsensiResource.php`
- `app/Filament/Resources/Akunting/LaporanLabaRugiResource.php`
- `app/Filament/Resources/Akunting/LaporanInputTransaksiResource.php`
- `app/Filament/Resources/Akunting/LaporanNeracaResource.php`

Catatan:
- Resource “laporan” biasanya opsional untuk global search.
- `PosSaleResource` sengaja dikecualikan sebelumnya.
