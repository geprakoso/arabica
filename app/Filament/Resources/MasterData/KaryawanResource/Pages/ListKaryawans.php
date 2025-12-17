<?php

namespace App\Filament\Resources\MasterData\KaryawanResource\Pages;

use App\Filament\Resources\MasterData\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKaryawans extends ListRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Karyawan')
                ->icon('heroicon-m-plus'),
        ];
    }
}
