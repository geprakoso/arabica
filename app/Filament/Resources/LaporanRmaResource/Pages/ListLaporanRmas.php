<?php

namespace App\Filament\Resources\LaporanRmaResource\Pages;

use App\Filament\Resources\LaporanRmaResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanRmas extends ListRecords
{
    protected static string $resource = LaporanRmaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
