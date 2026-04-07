<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjadwalanPengiriman extends ViewRecord
{
    protected static string $resource = PenjadwalanPengirimanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
