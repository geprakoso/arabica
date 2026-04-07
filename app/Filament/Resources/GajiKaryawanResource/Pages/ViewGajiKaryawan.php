<?php

namespace App\Filament\Resources\GajiKaryawanResource\Pages;

use App\Filament\Resources\GajiKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGajiKaryawan extends ViewRecord
{
    protected static string $resource = GajiKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
