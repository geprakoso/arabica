<?php

namespace App\Filament\Resources\LaporanLabaRugiResource\Pages;

use App\Filament\Resources\LaporanLabaRugiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanLabaRugi extends ViewRecord
{
    protected static string $resource = LaporanLabaRugiResource::class;

    public function getTitle(): string
    {
        $monthLabel = LaporanLabaRugiResource::formatMonthLabel($this->getRecord()?->month_start);

        if ($monthLabel === '-') {
            return 'Laporan Laba Rugi';
        }

        return "Laporan Laba Rugi {$monthLabel}";
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
