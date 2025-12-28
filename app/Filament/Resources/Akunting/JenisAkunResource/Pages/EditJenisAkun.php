<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJenisAkun extends EditRecord
{
    protected static string $resource = JenisAkunResource::class;

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
                        ->title('Tidak bisa hapus jenis akun')
                        ->body("Masih ada {$blockedCount} transaksi terkait di Input Transaksi Toko. Hapus transaksi tersebut terlebih dahulu.")
                        ->danger()
                        ->send();

                    $action->halt();
                }),
        ];
    }
}
