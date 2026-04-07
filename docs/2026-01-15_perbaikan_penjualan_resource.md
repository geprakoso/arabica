# Perbaikan Penjualan Resource - 15 Januari 2026

**File**: `app/Filament/Resources/PenjualanResource.php`  
**Commit**: 355c787 (7023fe0, a053e3a)  
**Tipe**: Bug Fix & Code Quality

## Ringkasan Perubahan

Perbaikan pada `PenjualanResource` yang mencakup logika visibility tombol aksi dan refactoring kode untuk konsistensi dan kualitas yang lebih baik.

## Detail Perubahan

### 1. Perbaikan Logika Visibility Action Group

#### Masalah Sebelumnya
Action group (View, Edit, Delete) pada tabel penjualan memiliki logika visibility yang kurang tepat, menyebabkan tombol tersembunyi pada kondisi tertentu yang seharusnya ditampilkan.

#### Solusi
Memperbaiki method `->hidden()` pada `ActionGroup` dengan logika yang lebih akurat:

```php
->hidden(function (Penjualan $record): bool {
    $hasLines = $record->items()->exists() || $record->jasaItems()->exists();
    $grandTotal = (float) ($record->grand_total ?? 0);
    $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);
    $isUnpaid = $totalPaid < $grandTotal;

    // Tampilkan action jika belum lunas ATAU grand total <= 0
    if ($isUnpaid || $grandTotal <= 0) {
        return false;  // Tidak hidden = tampilkan
    }

    // Sembunyikan hanya jika sudah ada items DAN sudah lunas
    return $hasLines && $grandTotal > 0;
})
```

#### Logika Baru:
1. **Tampilkan action** jika:
   - Penjualan belum lunas (`$isUnpaid`)
   - Grand total <= 0 (penjualan kosong/invalid)
2. **Sembunyikan action** hanya jika:
   - Sudah ada line items (produk/jasa)
   - DAN sudah lunas penuh

#### Alasan:
- Penjualan yang belum lunas perlu bisa diedit untuk menambah pembayaran
- Penjualan kosong perlu bisa diedit/dihapus
- Hanya penjualan yang sudah complete (ada items + lunas) yang action-nya disembunyikan

### 2. Refactoring Email Invoice

#### File: `app/Mail/InvoicePenjualanMail.php`

**Perubahan**:
- Menyesuaikan sintaks anonymous function untuk konsistensi
- Mengurutkan ulang import statements sesuai PSR-12

**Before**:
```php
// Import tidak terurut
use Illuminate\\Mail\\Mailable;
use App\\Models\\Penjualan;
use Barryvdh\\DomPDF\\Facade\\Pdf;
```

**After**:
```php
// Import terurut alfabetis
use App\\Models\\Penjualan;
use App\\Models\\ProfilePerusahaan;
use Barryvdh\\DomPDF\\Facade\\Pdf;
use Illuminate\\Bus\\Queueable;
use Illuminate\\Mail\\Mailable;
use Illuminate\\Queue\\SerializesModels;
```

## Testing

### Test Case 1: Penjualan Belum Lunas
- **Kondisi**: Grand total Rp 1.000.000, dibayar Rp 500.000
- **Expected**: Action group (View/Edit/Delete) **TAMPIL**
- **Reason**: Perlu bisa edit untuk menambah pembayaran

### Test Case 2: Penjualan Lunas dengan Items
- **Kondisi**: Grand total Rp 1.000.000, dibayar Rp 1.000.000, ada 2 items
- **Expected**: Action group **TERSEMBUNYI**
- **Reason**: Transaksi sudah complete

### Test Case 3: Penjualan Kosong
- **Kondisi**: Grand total Rp 0, tidak ada items
- **Expected**: Action group **TAMPIL**
- **Reason**: Perlu bisa diedit atau dihapus

### Test Case 4: Penjualan Lunas Tapi Kosong
- **Kondisi**: Grand total Rp 0, dibayar Rp 0, tidak ada items
- **Expected**: Action group **TAMPIL**
- **Reason**: Grand total <= 0 (kondisi invalid)

## Impact

### Positive
- ✅ User bisa edit penjualan yang belum lunas
- ✅ User bisa cleanup penjualan kosong/invalid
- ✅ Kode lebih konsisten dan mudah di-maintain
- ✅ Import statements terorganisir dengan baik

### Potential Issues
- Tidak ada breaking changes
- Backward compatible dengan data existing

## Files Changed

1. `app/Filament/Resources/PenjualanResource.php`
   - Lines 294-305: Logic visibility action group
   
2. `app/Mail/InvoicePenjualanMail.php`
   - Lines 1-11: Import statement ordering

## Related Commits

- `355c787`: Merge branch 'main'
- `7023fe0`: fix penjualan
- `a053e3a`: Merge branch 'main'
- `05e3be6`: refactor: Adjust anonymous function syntax

## Referensi

- [Filament Actions Documentation](https://filamentphp.com/docs/3.x/tables/actions)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
