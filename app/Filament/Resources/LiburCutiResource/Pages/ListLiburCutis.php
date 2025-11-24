<?php

namespace App\Filament\Resources\LiburCutiResource\Pages;

use App\Filament\Resources\LiburCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiburCutis extends ListRecords
{
    protected static string $resource = LiburCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
