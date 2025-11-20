<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use App\Models\Gudang;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // // 1. Ambil Lokasi Kantor (Misalnya kita ambil Gudang Pusat atau berdasarkan user)
        // // Disini saya hardcode ambil Gudang pertama, nanti bisa disesuaikan relasi user->gudang
        //     $kantor = Gudang::first(); 

        //     if (!$kantor) {
        //         Notification::make()->title('Data Kantor/Gudang belum diatur!')->danger()->send();
        //         $this->halt();
        //     }

        // // 2. Ambil Lokasi Inputan User (Dari Form)
        //     $userLat = $data['lat_absen'] ?? null;
        //     $userLong = $data['long_absen'] ?? null;

        //     if (!$userLat || !$userLong) {
        //      // Jika browser gagal ambil lokasi
        //     Notification::make()->title('Lokasi Anda tidak terdeteksi! Pastikan GPS aktif.')->danger()->send();
        //     $this->halt();
        //     }

        // // 3. Hitung Jarak (Haversine Formula)
        //     $jarak = $this->hitungJarak($kantor->latitude, $kantor->longitude, $userLat, $userLong);

        // // 4. Cek apakah dalam radius (meter)
        //     if ($jarak > $kantor->radius_km) {
        //         Notification::make()
        //             ->title('Gagal Absen!')
        //             ->body("Anda berada $jarak meter dari kantor. Maksimal jarak adalah {$kantor->radius_km} meter.")
        //             ->danger()
        //             ->send();
                
                $allowedIps = ['103.100.xxx.xxx', '127.0.0.1']; // Tambahkan localhost untuk test

                $userIp = request()->ip();

                if (!in_array($userIp, $allowedIps)) {
                    Notification::make()
                        ->title('Akses Ditolak!')
                        ->body("Anda harus terhubung ke Wi-Fi Kantor untuk absen. IP Anda: $userIp")
                        ->danger()
                        ->send();

                $this->halt(); // Batalkan proses simpan
            }

        return $data;
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
}