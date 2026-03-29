<?php

namespace App\Filament\Resources\RmaResource\Pages;

use App\Filament\Resources\RmaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRma extends EditRecord
{
    protected static string $resource = RmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
