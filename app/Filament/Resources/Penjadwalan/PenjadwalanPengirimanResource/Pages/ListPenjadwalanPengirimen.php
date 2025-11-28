<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjadwalanPengirimen extends ListRecords
{
    protected static string $resource = PenjadwalanPengirimanResource::class;
    protected static ?string $title = 'Penjadwalan Pengiriman';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah')
                ->icon('hugeicons-add-01'),
        ];
    }
}
