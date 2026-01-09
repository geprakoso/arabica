<?php

namespace App\Filament\Widgets\AtributCrosscheck;

use App\Filament\Resources\Penjadwalan\Service\CrosscheckResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CrosscheckTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return CrosscheckResource::table(
            $table->query(CrosscheckResource::getEloquentQuery())
        )
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->form(CrosscheckResource::getFormSchema())
                ->modalWidth('md'),
        ]);
    }
}
