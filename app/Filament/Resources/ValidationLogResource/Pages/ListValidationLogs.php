<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

use App\Filament\Resources\ValidationLogResource;
use Filament\Resources\Pages\ListRecords;

class ListValidationLogs extends ListRecords
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - logs are created automatically
        ];
    }
}
