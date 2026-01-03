<?php

namespace App\Filament\Resources\PenjualanReportResource\Pages;

use App\Filament\Resources\PenjualanReportResource;
use App\Filament\Exports\PenjualanExporter;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ExportAction;

class ListPenjualanReports extends ListRecords
{
    protected static string $resource = PenjualanReportResource::class;
}
