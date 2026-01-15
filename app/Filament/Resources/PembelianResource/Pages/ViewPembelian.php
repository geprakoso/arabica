<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use App\Filament\Resources\PembelianResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;
    protected static ?String $title = 'Detail Pembelian';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah')
                ->icon('heroicon-m-pencil-square'),
            Action::make('delete')
                ->label('Hapus')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus Pembelian')
                ->modalDescription('Pembelian yang masih dipakai transaksi lain akan diblokir.')
                ->action(function (): void {
                    try {
                        $this->record->delete();

                        Notification::make()
                            ->title('Pembelian dihapus')
                            ->success()
                            ->send();

                        $this->redirect(PembelianResource::getUrl('index'));
                    } catch (ValidationException $exception) {
                        $messages = collect($exception->errors())
                            ->flatten()
                            ->implode(' ');

                        Notification::make()
                            ->title('Gagal menghapus')
                            ->body($messages ?: 'Gagal menghapus pembelian.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
