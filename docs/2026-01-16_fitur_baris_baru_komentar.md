# Dokumentasi: Fitur Baris Baru pada Komentar Tugas

## Gambaran Umum
Dokumentasi ini menjelaskan implementasi fitur baris baru (newline) pada sistem komentar tugas di Penjadwalan Tugas. Fitur ini memungkinkan pengguna untuk membuat komentar multi-baris dengan menekan tombol Enter, dan baris baru tersebut akan ditampilkan dengan benar.

## Masalah yang Diselesaikan
Sebelumnya, ketika pengguna menekan Enter untuk membuat baris baru dalam textarea komentar, teks tetap ditampilkan secara inline (satu baris) setelah dikirim. Hal ini membuat komentar panjang sulit dibaca.

## Solusi Teknis

### 1. Perubahan pada Input Textarea
**File**: [`resources/views/livewire/task-comments.blade.php`](file:///www/wwwroot/arabica/resources/views/livewire/task-comments.blade.php#L42-L48)

```html
<textarea 
    wire:model.defer="body" 
    placeholder="Tulis komentar..."
    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-primary-500 focus:border-primary-500 resize-none"
    rows="3"
    wrap="soft"
></textarea>
```

**Perubahan Kunci**:
- `wire:model.defer="body"` - Menunda sinkronisasi data hingga form dikirim, mencegah Livewire menormalisasi input
- `wrap="soft"` - Memastikan baris baru ditangkap saat pengguna menekan Enter

### 2. Perubahan pada Tampilan Komentar
**File**: [`resources/views/livewire/task-comments.blade.php`](file:///www/wwwroot/arabica/resources/views/livewire/task-comments.blade.php#L27)

```html
<div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" style="white-space: pre-line;">
    {{ $comment->body }}
</div>
```

**Perubahan Kunci**:
- `style="white-space: pre-line;"` - Menggunakan inline style untuk memastikan CSS tidak ditimpa oleh aturan lain
- Properti CSS ini mempertahankan karakter baris baru (`\n`) dan menampilkannya sebagai line break visual

## Cara Kerja

### Alur Data
1. **Input Pengguna**: Pengguna mengetik komentar multi-baris di textarea
2. **Penangkapan**: `wire:model.defer` menunggu hingga form dikirim untuk menyinkronkan data
3. **Pengiriman**: Form mengirim data dengan baris baru yang dipertahankan sebagai karakter `\n`
4. **Penyimpanan**: Baris baru disimpan di database (kolom TEXT mendukung ini)
5. **Tampilan**: CSS `white-space: pre-line` merender `\n` sebagai line break visual

### Detail Teknis

#### `wire:model.defer` vs `wire:model`
- **`wire:model`**: Sinkronisasi pada setiap keystroke, dapat menormalisasi whitespace
- **`wire:model.defer`**: Sinkronisasi hanya saat form dikirim, mempertahankan input persis

#### CSS `white-space: pre-line`
- Mempertahankan karakter newline (`\n`)
- Menggabungkan beberapa spasi menjadi satu
- Membungkus teks secara alami di tepi container

## Cara Menggunakan

1. Buka detail tugas di Penjadwalan Tugas
2. Scroll ke bagian "Diskusi & Komentar"
3. Ketik komentar di textarea
4. Tekan **Enter** untuk membuat baris baru
5. Klik tombol **Kirim**
6. Komentar akan ditampilkan dengan baris baru yang dipertahankan

### Contoh
**Input**:
```
Baris pertama
Baris kedua
Baris ketiga
```

**Output**: Ditampilkan persis seperti yang diinput dengan line break yang jelas

## File yang Dimodifikasi

1. **[`app/Livewire/TaskComments.php`](file:///www/wwwroot/arabica/app/Livewire/TaskComments.php)**
   - Tidak ada perubahan pada logika backend
   - Validasi dan penyimpanan tetap sama

2. **[`resources/views/livewire/task-comments.blade.php`](file:///www/wwwroot/arabica/resources/views/livewire/task-comments.blade.php)**
   - Baris 43: Mengubah `wire:model` menjadi `wire:model.defer`
   - Baris 47: Menambahkan atribut `wrap="soft"`
   - Baris 27: Mengubah class CSS menjadi inline style `white-space: pre-line`

## Catatan Penting

- Fitur ini tidak memerlukan perubahan database
- Kompatibel dengan semua browser modern
- Tidak mempengaruhi komentar yang sudah ada
- Batas maksimal tetap 1000 karakter per komentar

## Troubleshooting

Jika baris baru tidak muncul:
1. Lakukan hard refresh browser (`Ctrl+Shift+R` atau `Cmd+Shift+R` di Mac)
2. Pastikan browser tidak dalam mode cache agresif
3. Periksa console browser untuk error JavaScript
