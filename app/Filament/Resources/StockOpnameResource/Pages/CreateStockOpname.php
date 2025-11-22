<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Resources\StockOpnameResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
