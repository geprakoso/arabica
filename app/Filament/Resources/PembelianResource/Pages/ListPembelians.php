<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListPembelians extends ListRecords
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
                Actions\CreateAction::make()
                    ->label('Pembelian')
                    ->icon('heroicon-s-plus'),
        ];
    }
}
