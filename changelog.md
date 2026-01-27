# CHANGELOG

Semua perubahan penting pada proyek ini akan didokumentasikan dalam file ini.

---

## [2026-01-16] - Perbaikan Filter & Fitur Baris Baru Komentar

### ✨ Fitur Baru
- **Dukungan Baris Baru pada Komentar Tugas**: Komentar sekarang mendukung multi-baris. Pengguna dapat menekan Enter untuk membuat baris baru dan format akan dipertahankan saat ditampilkan.

### 🔧 Perbaikan
- **Filter Default Tab Proses**: Filter tanggal "Hari Ini" sekarang tidak diterapkan pada tab "Proses", memungkinkan pengguna melihat semua tugas yang sedang dalam proses tanpa batasan tanggal.
- **Deteksi Tab Aktif**: Memperbaiki cara sistem mendeteksi tab aktif menggunakan Livewire component context untuk hasil yang lebih akurat.
- **Logika Filter Tanggal**: Filter "Hari Ini", "Kemarin", dll. sekarang menampilkan tugas yang **sedang berlangsung** pada tanggal tersebut (tanggal mulai <= filter <= deadline), bukan hanya tugas yang dimulai pada tanggal tersebut. Ini membuat filter lebih intuitif dan berguna.

### 📝 Perubahan Detail

#### 1. Penjadwalan Tugas - Filter & Tab
**File yang Diubah**:
- `app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource.php`
- `app/Filament/Resources/Penjadwalan/PenjadwalanTugasResource/Pages/ListPenjadwalanTugas.php`

**Perubahan**:
- Menambahkan property `activeTab` untuk tracking tab aktif
- Menambahkan method `getDefaultActiveTab()` untuk set tab default ke "Proses"
- Mengubah logika filter untuk skip filtering tanggal pada tab "Proses"
- Menggunakan `\Livewire\Livewire::current()->activeTab` untuk deteksi tab yang lebih reliable

**Dampak**:
- Tab "Proses" sekarang menampilkan semua tugas Pending/Proses tanpa filter tanggal
- Tab lain (Selesai, Batal, Semua) tetap menggunakan filter "Hari Ini" sebagai default
- Pengalaman pengguna lebih baik dengan default tab yang lebih relevan
- Filter tanggal sekarang menampilkan tugas yang sedang berlangsung, bukan hanya yang dimulai pada tanggal tertentu

#### 2. Sistem Komentar - Dukungan Baris Baru
**File yang Diubah**:
- `resources/views/livewire/task-comments.blade.php`

**Perubahan**:
- Mengubah `wire:model` menjadi `wire:model.defer` pada textarea untuk mencegah normalisasi input
- Menambahkan atribut `wrap="soft"` pada textarea untuk menangkap baris baru
- Mengubah CSS dari class `whitespace-pre-line` menjadi inline style `style="white-space: pre-line;"` untuk prioritas lebih tinggi

**Dampak**:
- Pengguna dapat membuat komentar multi-baris dengan menekan Enter
- Baris baru ditampilkan dengan benar, meningkatkan keterbacaan komentar panjang
- Tidak ada perubahan pada komentar yang sudah ada

### 📚 Dokumentasi
- Menambahkan `docs/2026-01-16_fitur_baris_baru_komentar.md` - Dokumentasi lengkap fitur baris baru pada komentar
- Menambahkan `docs/2026-01-16_filter_default_tab_proses.md` - Dokumentasi filter default tab proses
- Menambahkan `docs/2026-01-16_perbaikan_logika_filter_tanggal.md` - Dokumentasi perbaikan logika filter tanggal
- Menambahkan `docs/2026-01-16_fix_route_note_defined_error.md` - Dokumentasi perbaikan error route

### 🔍 Catatan Teknis
- Tidak ada perubahan database diperlukan
- Kompatibel dengan semua browser modern
- Tidak mempengaruhi performa sistem
- Backward compatible dengan data existing

---

## Format Changelog
Format berdasarkan [Keep a Changelog](https://keepachangelog.com/id/1.0.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/lang/id/).

### Kategori
- `✨ Fitur Baru` - Fitur baru yang ditambahkan
- `🔧 Perbaikan` - Bug fixes
- `💥 Breaking Changes` - Perubahan yang tidak backward compatible
- `🗑️ Deprecated` - Fitur yang akan dihapus di versi mendatang
- `🔒 Keamanan` - Perbaikan keamanan
- `📝 Perubahan Detail` - Penjelasan detail perubahan
- `📚 Dokumentasi` - Perubahan dokumentasi
