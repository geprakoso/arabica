# Implementasi Grand Total (Penjualan - Pembelian)

Dokumen ini menjelaskan logika perhitungan **Grand Total** yang digunakan pada `TukarTambahResource`. Logika ini menangani perhitungan dinamis antara dua transaksi yang berbeda (Penjualan dan Pembelian) dalam satu form.

## 1. Konsep Dasar

Tukar Tambah terdiri dari dua bagian utama:
*   **Penjualan (Barang Keluar)**: Barang yang kita jual ke pelanggan (menambah tagihan).
*   **Pembelian (Barang Masuk)**: Barang yang pelanggan tukarkan/jual ke kita (mengurangi tagihan).

**Rumus Grand Total:**
$$ \text{Grand Total} = (\text{Total Penjualan} + \text{Total Jasa}) - \text{Total Pembelian} $$

## 2. Struktur Form

Struktur data dalam form Filament diatur menggunakan `Group` dan `Repeater` yang bersarang:

```php
// Root Form
├── penjualan (Group)
│   ├── items (Repeater) -> Produk yang dijual
│   │   └── Row: qty, harga_jual
│   └── jasa_items (Repeater) -> Jasa yang dijual
│       └── Row: qty, harga
├── pembelian (Group)
│   └── items (Repeater) -> Produk yang ditukar tambah
│       └── Row: qty, hpp
└── grand_total_tukar_tambah (Placeholder) -> Menampilkan hasil hitungan
```

## 3. Implementasi Logika (Reactive)

Perhitungan dilakukan secara *real-time* di sisi klien (browser) menggunakan fitur reactive Filament (`live`, `afterStateUpdated`).

### A. Trigger Perubahan
Setiap kali field `qty`, `harga_jual`, atau `hpp` berubah di dalam repeater, kita memicu perhitungan ulang.

Contoh pada `pembelian.items`:

```php
TextInput::make('qty')
    ->numeric()
    ->lazy() // Atau live()
    ->afterStateUpdated(function (Set $set, Get $get): void {
        // 1. Hitung ulang Total Pembelian (Subtotal)
        $items = $get('../../items') ?? []; // Mengambil items dalam group pembelian
        $totalPembelian = collect($items)->sum(fn($item) => $item['qty'] * $item['hpp']);
        $set('../../total_pembelian_summary', number_format($totalPembelian, 0, ',', '.'));

        // 2. Hitung ulang Grand Total (Root Level)
        // Kita perlu mengakses data Penjualan yang ada di group tetangga (../../../../penjualan)
        $penjualanItems = $get('../../../../penjualan/items') ?? []; 
        $penjualanJasaItems = $get('../../../../penjualan/jasa_items') ?? [];
        
        // ... Hitung total penjualan ...
        $grandTotal = $totalPenjualan - $totalPembelian;
        
        // Update Placeholder di Root
        $set('../../../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
    })
```

### B. Navigasi Path (`../`)
Tantangan utama adalah mengakses state yang berada di luar repeater saat ini. Filament menggunakan path relatif:

*   **Dalam Field Repeater** (misal: `qty`):
    *   `$get('harga')` → Field sebelah (sibling)
    *   `$get('../../items')` → Array repeater parent
    *   `$get('../../../../penjualan/items')` → Naik ke root, lalu masuk ke group `penjualan`.
    
    *Catatan: Kedalaman `../` tergantung struktur nesting form Anda. Gunakan `dd($get('../../CHECK_PATH'))` untuk debug.*

## 4. Komponen Placeholder (Display Only)

Untuk menampilkan Grand Total, gunakan `Placeholder`. Komponen ini tidak menyimpan data ke database secara langsung (kecuali `dehydrated`), tapi berguna untuk tampilan user.

```php
TextInput::make('grand_total_tukar_tambah') // Bisa juga Placeholder::make
    ->label('Grand Total (Penjualan - Pembelian)')
    ->default(0)
    // Gunakan calculated content untuk initial load / refresh
    ->content(function (Get $get): string {
         // Logic perhitungan sama seperti di atas
         // Diakses saat form dimuat
         return 'Rp ' . ...;
    })
    ->disabled()
    ->dehydrated(false); // Jangan simpan ke DB kolom ini, hitung ulang di backend saat save jika perlu
```

## 5. Ringkasan Implementasi

1.  **State Paths:** Pastikan nama field dalam Repeater konsisten.
2.  **Reactivity:** Gunakan `lazy()` atau `live(onBlur: true)` pada field angka (`qty`, `harga`) untuk performa lebih baik daripada `live()` murni.
3.  **Cross-Access:** Saat mengubah data di `Pembelian`, kita harus "mengintip" data `Penjualan` untuk menghitung selisihnya, begitu juga sebaliknya.
4.  **Format:** Gunakan `number_format` untuk tampilan Rupiah pada `Set $set`.

## 6. Contoh Kode Lengkap (Template)

```php
// Simpan logika hitung dalam fungsi helper agar reusable dan tidak duplikat kode
$recalculateGrandTotal = function (Set $set, Get $get) {
    // Ambil data Penjualan
    // Akses path menyesuaikan posisi pemanggil
    $penjualanItems = $get('penjualan.items') ?? []; 
    $pembelianItems = $get('pembelian.items') ?? [];
    
    // ... hitung sum ...
    
    $set('grand_total_field', $result);
};
```

*Tips: Jika logika terlalu kompleks dengan banyak `../`, pertimbangkan memindahkan state calculation ke Livewire Component property atau gunakan `get` absolute path jika didukung versi Filament terbaru.*
