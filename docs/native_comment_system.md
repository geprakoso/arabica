# Panduan Implementasi Sistem Komentar Native (Native Comment System)

Dokumen ini menjelaskan langkah-langkah standar untuk mengimplementasikan fitur komentar pada Resource Filament menggunakan pendekatan Native (Livewire + Database).

## 1. Persiapan Database & Model

Pastikan tabel `task_comments` (atau tabel komentar generik jika ingin polimorfik) sudah ada. Untuk implementasi saat ini pada **Penjadwalan Tugas**:

### A. Migrasi
Buat tabel `task_comments`:
```php
Schema::create('task_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('penjadwalan_tugas_id')->constrained('penjadwalan_tugas')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->text('body');
    $table->timestamps();
});
```

### B. Model (`TaskComment`)
Pastikan Model memiliki relasi ke User dan Parent Resource.
```php
class TaskComment extends Model {
    protected $fillable = ['penjadwalan_tugas_id', 'user_id', 'body'];

    public function user() { return $this->belongsTo(User::class); }
    public function task() { return $this->belongsTo(PenjadwalanTugas::class, 'penjadwalan_tugas_id'); }
}
```

### C. Relasi pada Parent Model
Tambahkan relasi `hasMany` pada Model utama (misal `PenjadwalanTugas`):
```php
public function comments() {
    return $this->hasMany(TaskComment::class);
}
```

## 2. Komponen Livewire (`TaskComments`)

Buat komponen Livewire untuk menangani UI dan Logika (`app/Livewire/TaskComments.php`).

### Fitur Utama:
1.  **Mount**: Menerima record induk (`$record`).
2.  **Submit**:
    -   Validasi input.
    -   **Otorisasi**: Cek apakah user berhak (misal: Creator atau Assigned User).
    -   Create comment.
    -   Kirim notifikasi sukses.
3.  **Render**: Menampilkan view dengan list komentar.

```php
public function submit() {
    // ... validasi ...
    // ... authorization check ...
    TaskComment::create([
        'penjadwalan_tugas_id' => $this->record->id,
        'user_id' => auth()->id(),
        'body' => $this->body
    ]);
    // ... reset input ...
}
```

## 3. Tampilan (Blade View)

Gunakan styling standar Filament/Tailwind agar konsisten (`resources/views/livewire/task-comments.blade.php`).
-   Gunakan `bg-gray-50` / `dark:bg-gray-800` untuk bubble chat.
-   Tampilkan Avatar user (`$comment->user->getFilamentAvatarUrl()`).
-   Gunakan `wire:poll` jika ingin real-time update (opsional, hati-hati performance).

## 4. Integrasi ke Filament Resource

Untuk menampilkan komentar di halaman **View** atau **Infolist**:

### A. Buat Wrapper View
Buat file `resources/views/filament/infolists/entries/task-comments.blade.php`:
```blade
<div class="w-full">
    @livewire('task-comments', ['record' => $getRecord()])
</div>
```

### B. Masukkan ke Infolist Schema
Pada `Resource::infolist()`, tambahkan `ViewEntry`. Disarankan letakkan di kolom utama atau `columnSpanFull`.

```php
\Filament\Infolists\Components\ViewEntry::make('comments')
    ->view('filament.infolists.entries.task-comments')
    // ->columnSpanFull() // Jika ingin lebar penuh di bawah
```

## 5. Indikator Komentar Belum Dibaca (Unread Indicator)

Fitur ini memungkinkan pengguna melihat apakah ada komentar baru yang belum mereka baca pada daftar tugas.

### Mekanisme
1.  **Tracking View**: Setiap kali pengguna membuka halaman `View` atau `Edit` tugas, sistem mencatat waktu tersebut di tabel `task_views`.
2.  **Display Logic**:
    -   Pada tabel list `PenjadwalanTugasResource`, terdapat kolom **Diskusi**.
    -   Sistem membandingkan `created_at` komentar terakhir dengan `last_viewed_at` user.
    -   **Badge Hijau**: Ada komentar baru setelah user terakhir melihat tugas (atau user belum pernah melihat tugas).
    -   **Badge Abu-abu**: User sudah melihat tugas setelah komentar terakhir dibuat.

### Komponen
-   **Model**: `TaskView` (table: `task_views`).
-   **Relation**: `PenjadwalanTugas` hasMany `views`.
-   **Logic**: Diimplementasikan pada `mount()` page `View` & `Edit`, dan pada `TextColumn` di `Resource`.

## 6. Integrasi Notifikasi Bar (Database Notifications)

Sistem mengirim notifikasi in-app kepada pihak terkait saat ada komentar baru.

### Logika Pengiriman
-   **Sender**: User yang login saat ini (pengirim komentar).
-   **Recipient**:
    1.  **Creator**: Pembuat tugas.
    2.  **Assignees**: Semua karyawan yang ditugaskan.
    -   *Kecuali*: Jika Sender adalah salah satu dari mereka, dia tidak menerima notifikasi sendiri.
-   **Konten**:
    -   **Title**: "Komentar baru di tugas: [Judul Tugas Truncated]"
    -   **Body**: "[Nama Pengirim]: [Isi Komentar Truncated]"
    -   **Action**: Tombol "Lihat" yang mengarah langsung ke halaman View tugas.

### Implementasi
Dilakukan pada method `submit()` di Livewire Component `TaskComments.php` menggunakan `Filament\Notifications\Notification`.

## 7. Badge Navigasi & Optimasi Performa

### Badge Navigasi (Sidebar)
Menampilkan indikator jumlah task yang membutuhkan perhatian user (Personalized).
-   **Logic**: Hanya menghitung tugas dimana user adalah **Creator** atau **Assignee**.
-   **Split Logic**:
    -   **New 🆕 (Blue/Info)**: Tugas yang belum pernah dibuka sama sekali oleh user.
    -   **Comments 💬 (Green/Success)**: Tugas yang memiliki komentar baru (unread).
-   **Display**: Jika keduanya ada, menampilkan "X 🆕 | Y 💬".

### Optimasi Database (N+1 Solution)
Untuk mencegah N+1 query pada List Page (saat me-render kolom "Diskusi" untuk setiap baris), dilakukan **Eager Loading**:
1.  **Model Relationships**:
    -   `latestComment()`: `hasOne()->latestOfMany()`.
    -   `currentUserView()`: `hasOne()->where('user_id', auth()->id())`.
2.  **Resource Query**:
    -   `getEloquentQuery()` memanggil `->with(['latestComment', 'currentUserView'])`.
3.  **Impact**: Mengurangi ratusan query menjadi hanya 3 query utama per halaman load.

## 8. Catatan Pengembangan Masa Depan
Jika ingin menerapkan ke modul lain (misal Proyek atau Tiket):
1.  Pertimbangkan membuat tabel `comments` menjadi **Polimorfik** (`commentable_type`, `commentable_id`) agar satu tabel bisa untuk semua modul.
2.  Ubah Komponen Livewire menjadi dinamis menerima `ModulModel`.
