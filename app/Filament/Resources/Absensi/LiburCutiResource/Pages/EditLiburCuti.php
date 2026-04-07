<?php

namespace App\Filament\Resources\Absensi\LiburCutiResource\Pages;

use App\Filament\Resources\Absensi\LiburCutiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLiburCuti extends EditRecord
{
    protected static string $resource = LiburCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
