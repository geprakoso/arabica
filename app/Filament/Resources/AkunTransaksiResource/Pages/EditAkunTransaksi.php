<?php

namespace App\Filament\Resources\AkunTransaksiResource\Pages;

use App\Filament\Resources\AkunTransaksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAkunTransaksi extends EditRecord
{
    protected static string $resource = AkunTransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
