<?php

namespace App\Filament\Resources\JasaResource\Pages;

use App\Filament\Resources\JasaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJasas extends ListRecords
{
    protected static string $resource = JasaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
