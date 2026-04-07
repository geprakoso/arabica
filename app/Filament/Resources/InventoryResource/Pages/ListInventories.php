<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
    protected function makeTable(): Table
    {
        return parent::makeTable()
            ->recordAction('detail')
            ->recordUrl(null);
    }
}
