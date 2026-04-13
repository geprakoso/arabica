<?php

namespace App\Filament\Resources\RmaResource\Pages;

use App\Filament\Resources\RmaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRmas extends ListRecords
{
    protected static string $resource = RmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
