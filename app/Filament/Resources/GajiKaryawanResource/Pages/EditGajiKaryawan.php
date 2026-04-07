<?php

namespace App\Filament\Resources\GajiKaryawanResource\Pages;

use App\Filament\Resources\GajiKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGajiKaryawan extends EditRecord
{
    protected static string $resource = GajiKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
