<?php

namespace App\Filament\Widgets\AtributCrosscheck;

use App\Filament\Resources\Penjadwalan\Service\ListGameResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListGameTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ListGameResource::table(
            $table->query(ListGameResource::getEloquentQuery())
        )
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->form(ListGameResource::getFormSchema())
                ->modalWidth('md'),
        ]);
    }
}
