<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

use App\Filament\Resources\ValidationLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewValidationLog extends ViewRecord
{
    protected static string $resource = ValidationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Read-only resource - no edit action
        ];
    }
}
