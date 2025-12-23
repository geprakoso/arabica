<?php

namespace App\Filament\Resources\Absensi\AbsensiResource\Pages;

use App\Filament\Resources\Absensi\AbsensiResource;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        $userId = Auth::id();
        $hariIni = now()->toDateString();
        $absensiHariIni = $userId
            ? Absensi::query()
                ->where('user_id', $userId)
                ->whereDate('tanggal', $hariIni)
                ->first()
            : null;
        $statusTidakPerluPulang = ['izin', 'sakit', 'alpha', 'alpa'];

        return [
            Actions\CreateAction::make()
                ->visible(fn () => $absensiHariIni === null)
                ->label('Absen Masuk')
                ->icon('heroicon-o-arrow-right-on-rectangle'),
            Actions\Action::make('pulang')
                ->label('Pulang')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $absensiHariIni !== null
                    && ! in_array($absensiHariIni->status, $statusTidakPerluPulang, true))
                ->disabled(fn () => $absensiHariIni?->jam_keluar !== null)
                ->action(function () use ($absensiHariIni): void {
                    if (! $absensiHariIni) {
                        Notification::make()
                            ->title('Tidak ada absensi hari ini')
                            ->body('Silakan absen masuk terlebih dahulu.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($absensiHariIni->jam_keluar) {
                        Notification::make()
                            ->title('Jam pulang sudah tercatat')
                            ->warning()
                            ->send();

                        return;
                    }

                    $jamPulang = now();

                    $absensiHariIni->update([
                        'jam_keluar' => $jamPulang->format('H:i:s'),
                    ]);

                    $user = Auth::user();
                    $notification = Notification::make()
                        ->title('Berhasil absen pulang')
                        ->body('Jam pulang tercatat pada ' . $jamPulang->format('H:i'))
                        ->success();

                    $notification->send();

                    if ($user) {
                        $notification->sendToDatabase($user);
                    }
                }),
        ];
    }
}
