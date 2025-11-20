<?php

namespace App\Filament\Resources\PembelianReportResource\Pages;

use App\Filament\Resources\PembelianReportResource;
use Filament\Resources\Pages\ListRecords;

class ListPembelianReports extends ListRecords
{
    protected static string $resource = PembelianReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
