<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Pages;

use App\Filament\Resources\Akunting\LaporanInputTransaksiResource;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanInputTransaksi extends ViewRecord
{
    protected static string $resource = LaporanInputTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        // Read-only view; no edit/delete actions needed.
        return [];
    }
}
