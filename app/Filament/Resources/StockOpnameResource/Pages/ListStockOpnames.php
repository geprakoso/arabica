<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
