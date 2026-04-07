<?php

namespace App\Filament\Actions;

use App\Filament\Actions\SummaryExportHeaderAction;
use App\Filament\Resources\InventoryResource;
use Illuminate\Database\Eloquent\Builder;

class InventoryExportHeaderAction extends SummaryExportHeaderAction
{
    public function getTableQuery(): Builder
    {
        return InventoryResource::getEloquentQuery();
    }
}
