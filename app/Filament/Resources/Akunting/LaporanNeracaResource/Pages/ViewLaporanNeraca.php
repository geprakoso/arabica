<?php

namespace App\Filament\Resources\Akunting\LaporanNeracaResource\Pages;

use App\Filament\Resources\Akunting\LaporanNeracaResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewLaporanNeraca extends ViewRecord
{
    protected static string $resource = LaporanNeracaResource::class;

    public function getTitle(): string
    {
        $monthLabel = LaporanNeracaResource::formatMonthLabel($this->getRecord()?->month_start);

        if ($monthLabel === '-') {
            return 'Laporan Neraca';
        }

        return "Laporan Neraca {$monthLabel}";
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}
