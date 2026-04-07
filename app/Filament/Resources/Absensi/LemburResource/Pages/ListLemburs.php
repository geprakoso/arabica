<?php

namespace App\Filament\Resources\Absensi\LemburResource\Pages;

use App\Filament\Resources\Absensi\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLemburs extends ListRecords
{
    protected static string $resource = LemburResource::class;

    protected function getHeaderActions(): array
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        $lemburHariIni = \App\Models\Lembur::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal', now())
            ->first();

        return [
            Actions\CreateAction::make()
                ->label('Buat Lembur')
                ->icon('heroicon-m-plus')
                ->button()
                ->color('primary')
                ->visible(fn () => $lemburHariIni === null),

            Actions\Action::make('selesai_lembur')
                ->label('Selesai Lembur')
                ->icon('heroicon-m-check')
                ->button()
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $lemburHariIni !== null && $lemburHariIni->jam_selesai === null)
                ->action(function () use ($lemburHariIni) {
                    $lemburHariIni->update([
                        'jam_selesai' => now()->format('H:i:s'),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Lembur Selesai')
                        ->body('Waktu selesai lembur berhasil dicatat.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
