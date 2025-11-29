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

        return [
            Actions\CreateAction::make()
                ->visible(fn () => $absensiHariIni === null),
            Actions\Action::make('pulang')
                ->label('Pulang')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $absensiHariIni !== null)
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

                    $absensiHariIni->update([
                        'jam_keluar' => now()->format('H:i:s'),
                    ]);

                    Notification::make()
                        ->title('Jam pulang berhasil disimpan')
                        ->success()
                        ->send();
                }),
        ];
    }
}
