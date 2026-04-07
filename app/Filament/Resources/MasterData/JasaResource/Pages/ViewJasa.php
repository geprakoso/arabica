<?php

namespace App\Filament\Resources\MasterData\JasaResource\Pages;

use App\Filament\Resources\MasterData\JasaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJasa extends ViewRecord
{
    protected static string $resource = JasaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
