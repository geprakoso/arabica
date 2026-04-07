<?php

namespace App\Filament\Resources\Absensi\LemburResource\Pages;

use App\Filament\Resources\Absensi\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLembur extends EditRecord
{
    protected static string $resource = LemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
