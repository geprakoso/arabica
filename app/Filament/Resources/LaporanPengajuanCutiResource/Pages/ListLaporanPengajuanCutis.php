<?php

namespace App\Filament\Resources\LaporanPengajuanCutiResource\Pages;

use App\Filament\Resources\LaporanPengajuanCutiResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanPengajuanCutis extends ListRecords
{
    protected static string $resource = LaporanPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
