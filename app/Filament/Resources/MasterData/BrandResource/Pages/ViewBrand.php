<?php

namespace App\Filament\Resources\MasterData\BrandResource\Pages;

use App\Filament\Resources\MasterData\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBrand extends ViewRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
