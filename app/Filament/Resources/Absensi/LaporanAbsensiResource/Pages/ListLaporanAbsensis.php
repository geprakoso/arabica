<?php

namespace App\Filament\Resources\Absensi\LaporanAbsensiResource\Pages;

use App\Filament\Resources\Absensi\LaporanAbsensiResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ExportAction;


class ListLaporanAbsensis extends ListRecords
{
    protected static string $resource = LaporanAbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ExportAction::make()
            //     ->label('Export')
            //     ->icon('heroicon-o-download')
            //     ->color('success')
            //     ->file('absensi.csv')
            //     // ->exporter(AbsensiExporter::class)
            //     ,
        ];
    }

    public function getTabs(): array
    {
        return [
            'ringkasan' => Tab::make('Ringkasan Karyawan')
                ->icon('heroicon-o-user-group'),
            'rincian' => Tab::make('Rincian Absensi')
                ->icon('heroicon-o-clipboard-document-list'),
        ];
    }

    public function updatedActiveTab(): void
    {
        parent::updatedActiveTab();

        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
    }
}
