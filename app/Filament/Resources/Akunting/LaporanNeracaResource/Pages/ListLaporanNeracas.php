<?php

namespace App\Filament\Resources\Akunting\LaporanNeracaResource\Pages;

use App\Filament\Resources\Akunting\LaporanNeracaResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanNeracas extends ListRecords
{
    protected static string $resource = LaporanNeracaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
