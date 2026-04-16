<?php

namespace App\Filament\Resources\ValidationLogResource\Pages;

use App\Filament\Resources\ValidationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateValidationLog extends CreateRecord
{
    protected static string $resource = ValidationLogResource::class;
}
