<?php

namespace App\Filament\Resources\LaporanInputTransaksiResource\Pages;

use App\Filament\Resources\LaporanInputTransaksiResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanInputTransaksis extends ManageRecords
{
    protected static string $resource = LaporanInputTransaksiResource::class;

    // Laporan hanya untuk baca; hilangkan aksi create.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
