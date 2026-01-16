# Dokumentasi: Perbaikan Logika Filter Tanggal

## Gambaran Umum
Dokumentasi ini menjelaskan perbaikan logika filter tanggal pada halaman Penjadwalan Tugas agar lebih intuitif dan berguna.

## Masalah Sebelumnya
Filter "Hari Ini" hanya memeriksa `tanggal_mulai = hari ini`. Ini berarti:
- Tugas yang dimulai tanggal 15/01/2026 dengan deadline 16/01/2026 **TIDAK** muncul saat filter "Hari Ini" (16/01) aktif
- Pengguna kehilangan visibilitas terhadap tugas yang masih berlangsung

## Solusi Baru
Filter sekarang menampilkan tugas yang **sedang aktif/berlangsung** pada tanggal yang dipilih.

### Logika Baru
Tugas ditampilkan jika:
```
tanggal_mulai <= tanggal_filter DAN deadline >= tanggal_filter
```

### Contoh Kasus
**Tugas A**:
- Tanggal Mulai: 15/01/2026
- Deadline: 16/01/2026

**Hasil Filter**:
- ✅ Filter "Hari Ini" (16/01): **MUNCUL** (karena 15/01 <= 16/01 <= 16/01)
- ✅ Filter "Kemarin" (15/01): **MUNCUL** (karena 15/01 <= 15/01 <= 16/01)
- ❌ Filter "2 Hari Lalu" (14/01): **TIDAK MUNCUL** (karena 14/01 < 15/01)

## Implementasi Teknis

### File yang Diubah
**[`app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php`](file:///www/wwwroot/arabica/app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php#L430-L436)**

### Kode Sebelumnya
```php
if ($range === 'hari_ini') {
    return $query->whereDate('tanggal_mulai', now());
}
```

### Kode Sesudahnya
```php
if ($range === 'hari_ini') {
    $today = now()->toDateString();
    return $query
        ->whereDate('tanggal_mulai', '<=', $today)
        ->whereDate('deadline', '>=', $today);
}
```

### Filter yang Terpengaruh
Semua preset filter menggunakan logika yang sama:
1. **Hari Ini** - Tugas yang aktif hari ini
2. **Kemarin** - Tugas yang aktif kemarin
3. **2 Hari Lalu** - Tugas yang aktif 2 hari lalu
4. **3 Hari Lalu** - Tugas yang aktif 3 hari lalu

### Filter Custom
Filter custom tetap menggunakan logika lama (hanya memeriksa `tanggal_mulai`) untuk memberikan fleksibilitas lebih kepada pengguna.

## Keuntungan

### 1. Visibilitas Lebih Baik
Pengguna dapat melihat semua tugas yang **masih relevan** pada tanggal tertentu, bukan hanya yang dimulai pada tanggal tersebut.

### 2. Lebih Intuitif
Ketika memilih "Hari Ini", pengguna mengharapkan melihat semua tugas yang perlu dikerjakan hari ini, termasuk yang dimulai kemarin tetapi deadline-nya hari ini.

### 3. Workflow Lebih Natural
Filter sekarang mencerminkan cara kerja sebenarnya - tugas yang sedang berlangsung lebih penting daripada tugas yang baru dimulai.

## Contoh Skenario Nyata

### Skenario 1: Tugas Multi-Hari
**Data**:
- Tugas: "Implementasi Fitur Login"
- Tanggal Mulai: 14/01/2026
- Deadline: 17/01/2026

**Hasil Filter** (hari ini = 16/01/2026):
- ✅ **Muncul** di filter "Hari Ini"
- ✅ **Muncul** di filter "Kemarin" (15/01)
- ✅ **Muncul** di filter "2 Hari Lalu" (14/01)
- ❌ **Tidak muncul** di filter "3 Hari Lalu" (13/01)

### Skenario 2: Tugas Satu Hari
**Data**:
- Tugas: "Meeting Pagi"
- Tanggal Mulai: 16/01/2026
- Deadline: 16/01/2026

**Hasil Filter** (hari ini = 16/01/2026):
- ✅ **Muncul** di filter "Hari Ini"
- ❌ **Tidak muncul** di filter lainnya

## Catatan Penting

### Tab "Proses"
Tab "Proses" **TIDAK** terpengaruh oleh perubahan ini karena filter tanggal di-skip sepenuhnya untuk tab tersebut.

### Filter Custom
Filter custom (dengan rentang tanggal manual) masih menggunakan logika lama untuk memberikan kontrol penuh kepada pengguna.

### Backward Compatibility
Perubahan ini tidak memerlukan migrasi database atau perubahan data existing.

## Query SQL yang Dihasilkan

### Sebelumnya
```sql
SELECT * FROM penjadwalan_tugas 
WHERE DATE(tanggal_mulai) = '2026-01-16'
```

### Sesudahnya
```sql
SELECT * FROM penjadwalan_tugas 
WHERE DATE(tanggal_mulai) <= '2026-01-16' 
  AND DATE(deadline) >= '2026-01-16'
```

## Testing

Untuk menguji perubahan ini:

1. Buat tugas dengan tanggal mulai kemarin dan deadline besok
2. Pilih filter "Hari Ini"
3. Tugas tersebut **harus muncul** dalam daftar
4. Pilih filter "Kemarin"
5. Tugas tersebut **harus muncul** dalam daftar
6. Pilih filter "2 Hari Lalu"
7. Tugas tersebut **tidak boleh muncul** dalam daftar

## Performa

Perubahan ini menggunakan dua kondisi WHERE dengan index pada kolom `tanggal_mulai` dan `deadline`, sehingga performa query tetap optimal.
