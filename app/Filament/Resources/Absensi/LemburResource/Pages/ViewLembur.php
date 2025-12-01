<?php

namespace App\Filament\Resources\Absensi\LemburResource\Pages;

use App\Filament\Resources\Absensi\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLembur extends ViewRecord
{
    protected static string $resource = LemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
