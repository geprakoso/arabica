<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Stock Adjustment')
                ->icon('heroicon-o-plus'),
        ];
    }
}
