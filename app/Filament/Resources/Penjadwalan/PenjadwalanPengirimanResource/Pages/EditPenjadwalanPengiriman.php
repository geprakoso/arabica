<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjadwalanPengiriman extends EditRecord
{
    protected static string $resource = PenjadwalanPengirimanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
