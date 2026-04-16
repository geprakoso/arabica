<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

use App\Filament\Resources\ValidationLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationLog extends ViewRecord
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('resolve')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-check-badge')
                ->modalIconColor('success')
                ->modalHeading('Tandai Sebagai Selesai')
                ->modalDescription('Apakah Anda yakin ingin menandai log validasi ini sebagai sudah diselesaikan?')
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->modalCancelActionLabel('Batal')
                ->visible(fn () => ! $this->record->is_resolved)
                ->form([
                    \Filament\Forms\Components\Textarea::make('resolution_notes')
                        ->label('Catatan Penyelesaian')
                        ->placeholder('Catatan opsional tentang bagaimana masalah ini diselesaikan...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->markAsResolved($data['resolution_notes'] ?? null);

                    \Filament\Notifications\Notification::make()
                        ->title('Log validasi berhasil ditandai selesai')
                        ->icon('heroicon-o-check-circle')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }
}
