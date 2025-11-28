<?php

namespace App\Filament\Resources\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenjadwalanService extends EditRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
