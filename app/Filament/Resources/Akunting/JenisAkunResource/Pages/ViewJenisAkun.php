<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJenisAkun extends ViewRecord
{
    protected static string $resource = JenisAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
