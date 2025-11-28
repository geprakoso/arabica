<?php

namespace App\Filament\Resources\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenjadwalanService extends ViewRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
