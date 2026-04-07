# Dokumentasi Tukar Tambah (Trade-in)

> **Terakhir diperbarui**: 16 Januari 2026

## Deskripsi

Fitur **Tukar Tambah** memungkinkan transaksi di mana pelanggan menjual barang bekas (pembelian) sekaligus membeli barang baru (penjualan) dalam satu transaksi terintegrasi.

## Struktur Data

```
TukarTambah
├── no_nota (format: TT-YYYYMMDD-0001)
├── Penjualan (barang keluar ke pelanggan)
│   ├── items (produk yang dijual)
│   │   ├── id_produk
│   │   ├── qty
│   │   ├── harga_jual
│   │   └── kondisi
│   ├── jasa_items (layanan tambahan)
│   │   ├── jasa_id
│   │   ├── qty
│   │   └── harga
│   └── pembayaran (metode pembayaran)
└── Pembelian (barang masuk dari pelanggan)
    ├── items (barang yang dibeli)
    │   ├── id_produk
    │   ├── qty
    │   ├── hpp (Harga Pokok Pembelian)
    │   ├── harga_jual (rencana jual)
    │   └── kondisi
    └── pembayaran
```

## Perhitungan Grand Total

Grand Total menghitung selisih yang harus dibayar pelanggan:

```
Grand Total = Total Penjualan - Total Pembelian
```

### Formula Detail

```php
// Total Penjualan
$penjualanTotal = Σ(items: qty × harga_jual) + Σ(jasa_items: qty × harga)

// Total Pembelian  
$pembelianTotal = Σ(items: qty × hpp)

// Grand Total
$grandTotal = $penjualanTotal - $pembelianTotal
```

### Contoh Perhitungan

| Transaksi | Item | Qty | Harga | Subtotal |
|-----------|------|-----|-------|----------|
| **Penjualan** | iPhone 15 | 1 | Rp 15.000.000 | Rp 15.000.000 |
| **Penjualan** | Case HP | 1 | Rp 200.000 | Rp 200.000 |
| **Pembelian** | iPhone 13 (bekas) | 1 | Rp 8.000.000 | Rp 8.000.000 |

**Grand Total** = (Rp 15.000.000 + Rp 200.000) - Rp 8.000.000 = **Rp 7.200.000**

## Implementasi Teknis

### Lokasi File

```
app/Filament/Resources/
├── TukarTambahResource.php          # Resource utama
└── TukarTambahResource/
    └── Pages/
        └── CreateTukarTambah.php    # Halaman create
```

### Grand Total dengan Placeholder

Grand Total menggunakan `Placeholder` component untuk kalkulasi real-time:

```php
Placeholder::make('grand_total_tukar_tambah')
    ->label('Grand Total (Penjualan - Pembelian)')
    ->content(function (Get $get): string {
        // Calculate Penjualan total
        $penjualanItems = $get('penjualan.items') ?? [];
        $penjualanJasaItems = $get('penjualan.jasa_items') ?? [];
        
        $productTotal = collect($penjualanItems)->sum(fn ($item) => 
            (int)($item['qty'] ?? 0) * (int)($item['harga_jual'] ?? 0)
        );
        
        $serviceTotal = collect($penjualanJasaItems)->sum(fn ($item) => 
            (int)($item['qty'] ?? 0) * (int)($item['harga'] ?? 0)
        );
        
        // Calculate Pembelian total
        $pembelianItems = $get('pembelian.items') ?? [];
        $pembelianTotal = collect($pembelianItems)->sum(fn ($item) =>
            (int)($item['qty'] ?? 0) * (int)($item['hpp'] ?? 0)
        );
        
        $grandTotal = ($productTotal + $serviceTotal) - $pembelianTotal;
        
        return 'Rp ' . number_format($grandTotal, 0, ',', '.');
    })
```

> **Catatan**: Menggunakan `Placeholder` dengan `content()` callback lebih reliable dibanding `TextInput` dengan `afterStateHydrated` untuk mengakses data dari multiple statePaths.

### Reactive Updates

Untuk update otomatis saat user mengubah item, gunakan `lazy()` pada field input:

```php
TextInput::make('qty')
    ->lazy()  // Update saat blur, bukan setiap keystroke
    ->afterStateUpdated(function (Set $set, Get $get): void {
        // Recalculate totals
    })
```

### Default Supplier

Pembelian otomatis menggunakan supplier "User Jual":

```php
Hidden::make('id_supplier')
    ->default(function (): int {
        $supplier = Supplier::firstOrCreate(
            ['nama_supplier' => 'User Jual'],
            ['no_hp' => '0000']
        );
        return $supplier->id;
    })
```

## Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| **Nota Tunggal** | Satu nomor nota untuk Penjualan & Pembelian |
| **Grand Total Real-time** | Kalkulasi otomatis saat item berubah |
| **Lazy Input** | Tidak mengganggu typing user |
| **Supplier Default** | Otomatis "User Jual" untuk pembelian |
| **Summary Fields** | Total items & total harga per section |

## Troubleshooting

### Grand Total tidak update
- Pastikan menggunakan `Placeholder` dengan `content()` callback
- Gunakan absolute path: `penjualan.items`, `pembelian.items`

### Typing terganggu di field hpp/qty
- Gunakan `lazy()` bukan `reactive()` pada TextInput
- `lazy()` update saat blur, bukan setiap keystroke

### Path tidak bisa diakses
- Gunakan dot notation: `penjualan.items`
- Untuk nested: `../../grand_total_tukar_tambah`
