<?php

namespace App\Filament\Resources\Absensi\LiburCutiResource\Pages;

use App\Filament\Resources\Absensi\LiburCutiResource;
use App\Filament\Resources\Absensi\LiburCutiResource\Widgets\LiburCutiStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLiburCutis extends ListRecords
{
    protected static string $resource = LiburCutiResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LiburCutiStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Libur Cuti')
                ->icon('heroicon-s-plus'),
        ];
    }
}
