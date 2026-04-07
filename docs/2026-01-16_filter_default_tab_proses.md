# Dokumentasi: Filter Default Tab Proses

## Gambaran Umum
Dokumentasi ini menjelaskan implementasi filter default "Hari Ini" yang tidak diterapkan pada tab "Proses" di halaman Penjadwalan Tugas.

## Masalah yang Diselesaikan
Sebelumnya, filter tanggal "Hari Ini" diterapkan ke semua tab termasuk tab "Proses". Hal ini menyebabkan tugas yang sedang dalam proses tetapi dimulai di hari lain tidak muncul, membuat pengguna kesulitan melacak semua tugas yang sedang dikerjakan.

## Solusi Teknis

### 1. Tracking Tab Aktif
**File**: [`app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource/Pages/ListPenjadwalanTugas.php`](file:///www/wwwroot/arabica/app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource/Pages/ListPenjadwalanTugas.php)

```php
class ListPenjadwalanTugas extends ListRecords
{
    protected static string $resource = PenjadwalanTugasResource::class;
    protected static ?string $title = 'Penjadwalan Tugas';

    public ?string $activeTab = null;  // Property untuk tracking tab aktif

    // Set default tab ke 'proses'
    public function getDefaultActiveTab(): string | int | null
    {
        return 'proses';
    }
    
    // ... kode lainnya
}
```

**Perubahan Kunci**:
- Menambahkan property `public ?string $activeTab = null;` untuk menyimpan tab yang sedang aktif
- Menambahkan method `getDefaultActiveTab()` yang mengembalikan `'proses'` sebagai tab default

### 2. Logika Filter Conditional
**File**: [`app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php`](file:///www/wwwroot/arabica/app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php#L418-L427)

```php
->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
    // Cek apakah sedang di tab 'proses' - jika ya, skip filter tanggal
    $livewire = \Livewire\Livewire::current();
    $activeTab = $livewire?->activeTab ?? null;
    
    if ($activeTab === 'proses') {
        return $query;  // Skip filter tanggal untuk tab proses
    }

    $range = $data['range'] ?? 'hari_ini';
    
    // ... logika filter tanggal untuk tab lain
})
```

**Perubahan Kunci**:
- Menggunakan `\Livewire\Livewire::current()` untuk mendapatkan instance Livewire component
- Mengakses property `activeTab` dari component untuk mengetahui tab yang aktif
- Jika tab aktif adalah `'proses'`, query langsung dikembalikan tanpa filter tanggal
- Tab lain tetap menggunakan filter "Hari Ini" sebagai default

## Cara Kerja

### Alur Logika
1. **Halaman Dibuka**: Default tab adalah "Proses" (dari `getDefaultActiveTab()`)
2. **Tab Proses Aktif**: 
   - Filter tanggal di-skip
   - Menampilkan SEMUA tugas dengan status Pending atau Proses, tanpa batasan tanggal
3. **Tab Lain Aktif** (Selesai, Batal, Semua):
   - Filter tanggal "Hari Ini" diterapkan sebagai default
   - Pengguna dapat mengubah filter ke "Kemarin", "2 Hari Lalu", dll.

### Definisi Tab

```php
public function getTabs(): array
{
    return [
        'proses' => Tab::make('Proses')
            ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [
                StatusTugas::Pending, 
                StatusTugas::Proses
            ])),
        'selesai' => Tab::make('Selesai')
            ->modifyQueryUsing(fn ($query) => $query->where('status', StatusTugas::Selesai)),
        'batal' => Tab::make('Batal')
            ->modifyQueryUsing(fn ($query) => $query->where('status', StatusTugas::Batal)),
        'all' => Tab::make('Semua'),
    ];
}
```

## Perilaku Filter per Tab

| Tab | Filter Tanggal Default | Dapat Diubah? | Menampilkan |
|-----|------------------------|---------------|-------------|
| **Proses** | Tidak ada (disabled) | ❌ Tidak | Semua tugas Pending/Proses |
| **Selesai** | Hari Ini | ✅ Ya | Tugas selesai hari ini |
| **Batal** | Hari Ini | ✅ Ya | Tugas batal hari ini |
| **Semua** | Hari Ini | ✅ Ya | Semua tugas hari ini |

## Keuntungan

1. **Visibilitas Lebih Baik**: Pengguna dapat melihat semua tugas yang sedang dikerjakan, tidak peduli kapan dimulai
2. **Workflow Lebih Natural**: Tab "Proses" fokus pada apa yang sedang dikerjakan, bukan kapan dimulai
3. **Fleksibilitas**: Tab lain tetap memiliki filter untuk memudahkan pencarian berdasarkan tanggal
4. **Default yang Masuk Akal**: Halaman terbuka langsung di tab "Proses" yang paling sering digunakan

## File yang Dimodifikasi

1. **[`app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php`](file:///www/wwwroot/arabica/app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php)**
   - Baris 418-427: Logika filter conditional berdasarkan tab aktif

2. **[`app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource/Pages/ListPenjadwalanTugas.php`](file:///www/wwwroot/arabica/app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource/Pages/ListPenjadwalanTugas.php)**
   - Baris 14: Property `activeTab`
   - Baris 42-45: Method `getDefaultActiveTab()`

## Catatan Penting

- Perubahan ini tidak mempengaruhi data di database
- Filter masih bisa digunakan secara manual di tab "Proses" jika diperlukan (UI tetap ada)
- Kompatibel dengan semua fitur existing lainnya
- Tidak ada breaking changes

## Troubleshooting

Jika filter masih diterapkan di tab "Proses":
1. Clear cache browser dan reload halaman
2. Pastikan tidak ada JavaScript error di console
3. Verifikasi bahwa Livewire component ter-update dengan benar
