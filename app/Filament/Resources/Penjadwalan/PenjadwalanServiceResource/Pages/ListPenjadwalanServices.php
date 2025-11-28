<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjadwalanServices extends ListRecords
{
    protected static string $resource = PenjadwalanServiceResource::class;
    protected static ?string $title = 'Penjadwalan Service';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah')
                ->icon('hugeicons-add-01'),
        ];
    }
}
