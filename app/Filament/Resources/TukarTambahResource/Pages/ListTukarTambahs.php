<?php

namespace App\Filament\Resources\TukarTambahResource\Pages;

use App\Filament\Resources\TukarTambahResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListTukarTambahs extends ListRecords
{
    protected static string $resource = TukarTambahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
