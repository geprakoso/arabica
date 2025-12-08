<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisAkun extends ListRecords
{
    protected static string $resource = JenisAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
