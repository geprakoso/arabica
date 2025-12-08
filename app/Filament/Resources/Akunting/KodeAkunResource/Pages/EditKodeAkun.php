<?php

namespace App\Filament\Resources\Akunting\KodeAkunResource\Pages;

use App\Filament\Resources\Akunting\KodeAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKodeAkun extends EditRecord
{
    protected static string $resource = KodeAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
