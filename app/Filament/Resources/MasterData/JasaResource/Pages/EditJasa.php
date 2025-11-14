<?php

namespace App\Filament\Resources\MasterData\JasaResource\Pages;

use App\Filament\Resources\MasterData\JasaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJasa extends EditRecord
{
    protected static string $resource = JasaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
