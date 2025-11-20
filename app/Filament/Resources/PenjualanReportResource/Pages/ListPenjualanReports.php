<?php

namespace App\Filament\Resources\PenjualanReportResource\Pages;

use App\Filament\Resources\PenjualanReportResource;
use Filament\Resources\Pages\ListRecords;

class ListPenjualanReports extends ListRecords
{
    protected static string $resource = PenjualanReportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
