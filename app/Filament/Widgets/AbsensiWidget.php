<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AbsensiWidget extends Widget
{
    protected static string $view = 'filament.widgets.absensi-widget';
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = '1/2';
    
    public $currentAbsensi;

    protected function getAbsensiData()
    {
        // Ambil data absensi hari ini untuk user yang sedang login
        $this->currentAbsensi = Absensi::where('user_id', Auth::id())
            ->where('tanggal', now()->toDateString())
            ->first();
    }

    protected function defaultLat(): float
    {
        return (float) config('services.absensi.lat', 0);
    }

    protected function defaultLong(): float
    {
        return (float) config('services.absensi.long', 0);
    }
    
    public function mount(): void
    {
        $this->getAbsensiData();
    }

    // Metode untuk absen masuk (Absen Masuk)
    public function checkIn(): void
    {
        if ($this->currentAbsensi) {
            Notification::make()
                ->title('Gagal Absen')
                ->body('Anda sudah absen masuk hari ini.')
                ->danger()
                ->send();
            return;
        }

        // Buat record absensi baru
        Absensi::create([
            'user_id' => Auth::id(),
            'tanggal' => now()->toDateString(),
            'status' => 'Hadir', // Default Hadir
            'jam_masuk' => now()->toTimeString(),
            'keterangan' => 'Absensi masuk via Dashboard.',
            'lat_absen' => $this->defaultLat(),
            'long_absen' => $this->defaultLong(),
        ]);

        $this->getAbsensiData(); // Refresh data setelah berhasil
        
        Notification::make()
            ->title('Absen Masuk Berhasil')
            ->body('Selamat bekerja! Jangan lupa absen pulang nanti.')
            ->success()
            ->send();
    }
    
    // Metode untuk absen pulang (Absen Pulang)
    public function checkOut(): void
    {
        if (!$this->currentAbsensi || $this->currentAbsensi->jam_keluar) {
            Notification::make()
                ->title('Gagal Absen')
                ->body('Anda belum absen masuk atau sudah absen pulang.')
                ->danger()
                ->send();
            return;
        }

        // Update record absensi dengan jam pulang
        $this->currentAbsensi->update([
            'jam_keluar' => now()->toTimeString(),
        ]);

        $this->getAbsensiData(); // Refresh data setelah berhasil
        
        Notification::make()
            ->title('Absen Pulang Berhasil')
            ->body('Sampai jumpa besok!')
            ->success()
            ->send();
    }
}
