<?php

namespace App\Filament\Resources\Penjadwalan\Service\ListGameResource\Pages;

use App\Filament\Resources\Penjadwalan\Service\ListGameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListGames extends ListRecords
{
    protected static string $resource = ListGameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
