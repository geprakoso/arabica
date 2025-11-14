<?php

namespace App\Filament\Resources\MasterData\KategoriResource\Pages;

use App\Filament\Resources\MasterData\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKategori extends ViewRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
