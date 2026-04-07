<?php

namespace App\Filament\Resources\Akunting\KodeAkunResource\Pages;

use App\Filament\Resources\Akunting\KodeAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKodeAkun extends ViewRecord
{
    protected static string $resource = KodeAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
