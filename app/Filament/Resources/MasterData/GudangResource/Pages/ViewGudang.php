<?php

namespace App\Filament\Resources\MasterData\GudangResource\Pages;

use App\Filament\Resources\MasterData\GudangResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGudang extends ViewRecord
{
    protected static string $resource = GudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
