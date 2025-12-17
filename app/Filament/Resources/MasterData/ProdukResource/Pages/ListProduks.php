<?php

namespace App\Filament\Resources\MasterData\ProdukResource\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Produk')
                ->icon('heroicon-m-plus'),
        ];
    }
}
