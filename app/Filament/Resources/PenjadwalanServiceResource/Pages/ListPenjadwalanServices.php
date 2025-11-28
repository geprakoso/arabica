<?php

namespace App\Filament\Resources\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjadwalanServices extends ListRecords
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
