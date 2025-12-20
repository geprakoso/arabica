<?php

namespace App\Filament\Resources\LaporanLabaRugiResource\Pages;

use App\Filament\Resources\LaporanLabaRugiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaporanLabaRugis extends ListRecords
{
    protected static string $resource = LaporanLabaRugiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
