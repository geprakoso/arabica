<?php

namespace App\Filament\Resources\MasterData\GudangResource\Pages;

use App\Filament\Resources\MasterData\GudangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGudang extends EditRecord
{
    protected static string $resource = GudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
