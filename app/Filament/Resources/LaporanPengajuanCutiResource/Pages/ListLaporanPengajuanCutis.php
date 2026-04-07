<?php

namespace App\Filament\Resources\LaporanPengajuanCutiResource\Pages;

use App\Filament\Resources\LaporanPengajuanCutiResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListLaporanPengajuanCutis extends ListRecords
{
    protected static string $resource = LaporanPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function makeTable(): Table
    {
        return parent::makeTable()
            ->recordAction('detail')
            ->recordUrl(null);
    }
}
