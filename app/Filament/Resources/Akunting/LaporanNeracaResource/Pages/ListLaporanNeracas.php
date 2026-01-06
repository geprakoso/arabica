<?php

namespace App\Filament\Resources\Akunting\LaporanNeracaResource\Pages;

use App\Filament\Resources\Akunting\LaporanNeracaResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListLaporanNeracas extends ListRecords
{
    protected static string $resource = LaporanNeracaResource::class;
    // protected static string $view = 'filament.resources.laporan-neraca.list';

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * Tabs:
     * - Bulanan: existing monthly list table (default)
     * - Detail: custom Neraca detail view
     */
    public function getTabs(): array
    {
        return [
            'bulanan' => Tab::make()->label('Bulanan'),
            'detail' => Tab::make()->label('Detail'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'bulanan';
    }

    public function getNeracaDetailData(): array
    {
        $latestRecord = LaporanNeracaResource::getEloquentQuery()->first();

        return LaporanNeracaResource::neracaViewData($latestRecord);
    }
}
