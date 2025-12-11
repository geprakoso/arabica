<?php

namespace App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInputTransaksiToko extends ViewRecord
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
