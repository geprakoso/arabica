<?php

namespace App\Filament\Resources\MasterData\AkunTransaksiResource\Pages;

use App\Filament\Resources\MasterData\AkunTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAkunTransaksi extends ViewRecord
{
    protected static string $resource = AkunTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
