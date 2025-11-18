<?php

namespace App\Filament\Resources\MasterData\KaryawanResource\Pages;

use App\Filament\Resources\MasterData\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKaryawan extends ViewRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
