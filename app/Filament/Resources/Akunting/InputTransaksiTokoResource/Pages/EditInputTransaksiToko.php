<?php

namespace App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
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
