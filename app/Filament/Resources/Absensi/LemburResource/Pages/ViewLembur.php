<?php

namespace App\Filament\Resources\Absensi\LemburResource\Pages;

use App\Filament\Resources\Absensi\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLembur extends ViewRecord
{
    protected static string $resource = LemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Terima')
                ->icon('heroicon-m-check')
                ->button()
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->getRecord()->status === \App\Enums\StatusPengajuan::Pending && auth()->user()->hasRole(['super_admin', 'admin']))
                ->action(function () {
                    $this->getRecord()->update([
                        'status' => \App\Enums\StatusPengajuan::Diterima,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Disetujui')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reject')
                ->label('Tolak')
                ->icon('heroicon-m-x-mark')
                ->button()
                ->color('danger')
                ->visible(fn () => $this->getRecord()->status === \App\Enums\StatusPengajuan::Pending && auth()->user()->hasRole(['super_admin', 'admin']))
                ->form([
                    \Filament\Forms\Components\Textarea::make('catatan')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->update([
                        'status' => \App\Enums\StatusPengajuan::Ditolak,
                        'catatan' => $data['catatan'],
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Pengajuan berhasil ditolak')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->color('gray')
                ->icon('heroicon-m-pencil')
                ->button()
                ->label('Edit'), // Restoring label for usability, 'gray' makes it white/neutral
            Actions\DeleteAction::make()
                ->color('gray')
                ->icon('heroicon-m-trash')
                ->button()
                ->label(''),
        ];
    }
}
