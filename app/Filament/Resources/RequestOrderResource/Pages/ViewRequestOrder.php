<?php

namespace App\Filament\Resources\RequestOrderResource\Pages;

use App\Filament\Resources\RequestOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRequestOrder extends ViewRecord
{
    protected static string $resource = RequestOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
