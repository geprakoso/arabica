<?php

namespace App\Filament\Resources\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\InputTransaksiTokoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInputTransaksiTokos extends ListRecords
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
