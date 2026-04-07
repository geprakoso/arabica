## Soft delete produk

Dokumen ini menjelaskan perubahan yang sudah dilakukan untuk soft delete pada tabel produk (`md_produk`), plus panduan singkat jika ingin menerapkan pola yang sama di resource lain.

### Perubahan yang sudah dilakukan

- Migration: menambah kolom `deleted_at` di `md_produk`.
  - File: `database/migrations/2026_01_27_000000_add_deleted_at_to_produk_table.php`
  - Catatan: memakai `softDeletes()` dan ada guard `Schema::hasColumn`.
- Contoh kode:

```php
Schema::table('md_produk', function (Blueprint $table): void {
    if (! Schema::hasColumn('md_produk', 'deleted_at')) {
        $table->softDeletes();
    }
});
```
- Model `Produk`: aktifkan soft delete.
  - File: `app/Models/Produk.php`
  - Perubahan: `use SoftDeletes;` dan cast `deleted_at` ke `datetime`.
- Contoh kode:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
}
```
- Resource produk: tampilkan filter trashed + aksi restore/force delete.
  - File: `app/Filament/Resources/MasterData/ProdukResource.php`
  - Perubahan:
    - Tambah `TrashedFilter`.
    - Tambah aksi `Delete`, `Restore`, `ForceDelete` (termasuk bulk).
    - Override `getEloquentQuery()` untuk menghapus `SoftDeletingScope`.
- Contoh kode:

```php
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

public static function table(Table $table): Table
{
    return $table
        ->filters([
            TrashedFilter::make(),
        ])
        ->actions([
            Tables\Actions\DeleteAction::make(),
            Tables\Actions\RestoreAction::make(),
            Tables\Actions\ForceDeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\RestoreBulkAction::make(),
            Tables\Actions\ForceDeleteBulkAction::make(),
        ]);
}

public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
}
```
- Relasi transaksi: histori tetap bisa menampilkan produk yang sudah dihapus.
  - File: `app/Models/PembelianItem.php` (relasi `produk()` pakai `withTrashed()`).
  - File: `app/Models/PenjualanItem.php` (relasi `produk()` pakai `withTrashed()`).
- Contoh kode:

```php
public function produk()
{
    return $this->belongsTo(Produk::class, 'id_produk')->withTrashed();
}
```

### Checklist implementasi di resource lain

Jika resource lain memakai `Produk` dan Anda ingin mendukung soft delete secara penuh:

1) **Query utama (list / table)**
   - Tambahkan `TrashedFilter::make()`.
   - Override `getEloquentQuery()` dan panggil:
     - `withoutGlobalScopes([SoftDeletingScope::class])`
   - Contoh:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScopes([SoftDeletingScope::class]);
}
```

2) **Actions**
   - Tambahkan aksi:
     - `Tables\Actions\DeleteAction::make()`
     - `Tables\Actions\RestoreAction::make()`
     - `Tables\Actions\ForceDeleteAction::make()`
   - Untuk bulk:
     - `DeleteBulkAction`, `RestoreBulkAction`, `ForceDeleteBulkAction`
   - Contoh:

```php
->actions([
    Tables\Actions\DeleteAction::make(),
    Tables\Actions\RestoreAction::make(),
    Tables\Actions\ForceDeleteAction::make(),
])
->bulkActions([
    Tables\Actions\DeleteBulkAction::make(),
    Tables\Actions\RestoreBulkAction::make(),
    Tables\Actions\ForceDeleteBulkAction::make(),
])
```

3) **Relasi Eloquent**
   - Jika histori transaksi harus tetap tampil walau produk dihapus:
     - gunakan `->withTrashed()` pada relasi `belongsTo(Produk::class)`.
   - Contoh:

```php
public function produk()
{
    return $this->belongsTo(Produk::class, 'id_produk')->withTrashed();
}
```

4) **Select/lookup Produk di form**
   - Jika tetap ingin menampilkan produk yang sudah dihapus:
     - gunakan `Produk::query()->withTrashed()` pada opsi select.
   - Jika tidak ingin tampilkan produk terhapus:
     - biarkan default (soft deleted akan tersembunyi).
   - Contoh (tetap tampilkan):

```php
Forms\Components\Select::make('id_produk')
    ->options(
        Produk::query()->withTrashed()->orderBy('nama_produk')->pluck('nama_produk', 'id')->all()
    );
```

### Migrasi

Jalankan sekali:

```bash
php artisan migrate
```
