<?php

namespace App\Filament\Resources\GajiKaryawanResource\Pages;

use App\Filament\Resources\GajiKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGajiKaryawans extends ListRecords
{
    protected static string $resource = GajiKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
