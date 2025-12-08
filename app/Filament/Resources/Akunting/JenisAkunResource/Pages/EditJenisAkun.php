<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisAkun extends EditRecord
{
    protected static string $resource = JenisAkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
