<?php

namespace App\Filament\Resources\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\InputTransaksiTokoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInputTransaksiToko extends EditRecord
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
