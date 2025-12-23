<?php

namespace App\Filament\Resources\Absensi\AbsensiResource\Pages;

use App\Models\Absensi;
use App\Models\ProfilePerusahaan;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Absensi\AbsensiResource;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;
    protected ?string $heading = '';

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Batasi 1 absensi per user per hari
        $sudahAbsen = Absensi::query()
            ->where('user_id', $data['user_id'] ?? Auth::user()->id())
            ->whereDate('tanggal', $data['tanggal'] ?? now()->toDateString())
            ->exists();

        if ($sudahAbsen) {
            Notification::make()
                ->title('Sudah absen hari ini')
                ->body('Anda hanya dapat melakukan absensi 1 kali per hari.')
                ->danger()
                ->send();

            $this->halt();
        }

        $status = $data['status'] ?? null;

        // Pulang sebaiknya UPDATE absensi yang sudah ada (bukan bikin record baru)
        // supaya tetap "1 absensi per user per hari".
        if ($status === 'pulang') {
            Notification::make()
                ->title('Pulang tidak bisa dibuat sebagai record baru')
                ->body('Gunakan tombol/aksi "Pulang" pada absensi hari ini (update jam pulang).')
                ->warning()
                ->send();

            $this->halt();
        }

        // Izin atau sakit tidak perlu validasi lokasi
        if (in_array($status, ['izin', 'sakit'], true)) {
            return $data;
        }

        // Hanya wajib cek koordinat saat status hadir
        if ($status !== 'hadir') {
            return $data;
        }

        // Radius maksimal (meter) dari titik kantor untuk validasi absensi
        $radiusMeter = 100;

        // Ambil titik kantor dari Profil Perusahaan
        $kantor = ProfilePerusahaan::first();

        if (!$kantor || !$kantor->lat_perusahaan || !$kantor->long_perusahaan) {
            Notification::make()
                ->title('Koordinat kantor belum diset')
                ->body('Setel Latitude/Longitude di Profil Perusahaan terlebih dahulu.')
                ->danger()
                ->send();

            $this->halt();
        }

        $userLat = $data['lat_absen'] ?? null;
        $userLong = $data['long_absen'] ?? null;

        // Pastikan browser memberi koordinat pengguna
        if (!$userLat || !$userLong) {
            Notification::make()
                ->title('Lokasi Anda tidak terdeteksi')
                ->body('Pastikan GPS/Location diizinkan di browser.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Hitung jarak dari pengguna ke titik kantor
        $jarak = $this->hitungJarak($kantor->lat_perusahaan, $kantor->long_perusahaan, $userLat, $userLong);

        // Tolak jika di luar radius
        if ($jarak > $radiusMeter) {
            Notification::make()
                ->title('Gagal Absen')
                ->body("Anda berada {$jarak} meter dari titik kantor. Maksimal jarak adalah {$radiusMeter} meter.")
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    // Rumus Matematika menghitung jarak 2 koordinat dalam Meter
    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius);
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        // Notifikasi berbeda berdasarkan status
        $status = $this->record->status;

        if ($status === 'hadir') {
            $notification =Notification::make()
                ->title('Berhasil absen masuk')
                ->body("Anda telah absen masuk pada {$this->record->tanggal->format('d-m-Y')} pukul {$this->record->jam_masuk}.")
                ->icon('heroicon-o-check-circle')
                ->success();
            
            $notification->send();
            $notification->sendToDatabase($user);

            return;
        }

        if (in_array($status, ['izin', 'sakit'], true)) {
            $notification = Notification::make()
                ->title('Pengajuan absensi tersimpan')
                ->body("Status: {$status} pada {$this->record->tanggal -> format('d-m-Y')}.")
                ->icon('heroicon-o-document-check');

            $notification->sendToDatabase($user);

            return;
        }

        // Fallback
        $notification = Notification::make()
            ->title('Absensi baru dibuat')
            ->body("Status: {$status} pada {$this->record->tanggal}.")
            ->icon('heroicon-o-check-circle');

        $notification->sendToDatabase($user);
    }
}
