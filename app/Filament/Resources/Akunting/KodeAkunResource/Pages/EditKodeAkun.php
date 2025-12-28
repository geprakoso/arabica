<?php

namespace App\Filament\Resources\Akunting\KodeAkunResource\Pages;

use App\Filament\Resources\Akunting\KodeAkunResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditKodeAkun extends EditRecord
{
    protected static string $resource = KodeAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (Actions\DeleteAction $action): void {
                    $record = $this->getRecord();

                    $blockedCount = $record->inputTransaksiTokos()->count();
                    if ($blockedCount === 0) {
                        return;
                    }

                    Notification::make()
                        ->title('Tidak bisa hapus kode akun')
                        ->body("Masih ada {$blockedCount} transaksi terkait di Input Transaksi Toko. Hapus transaksi atau jenis akun terkait terlebih dahulu.")
                        ->danger()
                        ->send();

                    $action->halt();
                }),
        ];
    }
}
