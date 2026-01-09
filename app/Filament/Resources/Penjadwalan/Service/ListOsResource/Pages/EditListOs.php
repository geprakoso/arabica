<?php

namespace App\Filament\Resources\Penjadwalan\Service\ListOsResource\Pages;

use App\Filament\Resources\Penjadwalan\Service\ListOsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditListOs extends EditRecord
{
    protected static string $resource = ListOsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
