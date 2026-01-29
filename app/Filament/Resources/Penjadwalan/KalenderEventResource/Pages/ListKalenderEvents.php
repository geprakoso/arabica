<?php

namespace App\Filament\Resources\Penjadwalan\KalenderEventResource\Pages;

use App\Filament\Resources\Penjadwalan\KalenderEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKalenderEvents extends ListRecords
{
    protected static string $resource = KalenderEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Event')
                ->icon('heroicon-o-plus'),
        ];
    }
}
