<?php

namespace App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInputTransaksiToko extends CreateRecord
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
