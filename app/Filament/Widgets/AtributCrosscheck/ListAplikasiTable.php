<?php

namespace App\Filament\Widgets\AtributCrosscheck;

use App\Filament\Resources\Penjadwalan\Service\ListAplikasiResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ListAplikasiTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ListAplikasiResource::table(
            $table->query(ListAplikasiResource::getEloquentQuery())
        )
        ->headerActions([
            Tables\Actions\CreateAction::make()
                ->form(ListAplikasiResource::getFormSchema())
                ->modalWidth('md'),
        ]);
    }
}
