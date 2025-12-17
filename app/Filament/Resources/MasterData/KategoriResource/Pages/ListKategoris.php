<?php

namespace App\Filament\Resources\MasterData\KategoriResource\Pages;

use App\Filament\Resources\MasterData\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategoris extends ListRecords
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Kategori')
                ->icon('heroicon-m-plus'),
        ];
    }
}
