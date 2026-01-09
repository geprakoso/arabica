<?php

namespace App\Filament\Widgets\AtributCrosscheck;

use App\Filament\Resources\Penjadwalan\Service\ListOsResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListOsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ListOsResource::table(
            $table->query(ListOsResource::getEloquentQuery())
        )
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->form(ListOsResource::getFormSchema())
                ->modalWidth('md'),
        ]);
    }
}
