<?php

namespace App\Filament\Resources\MasterData\GudangResource\Pages;

use App\Filament\Resources\MasterData\GudangResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGudang extends CreateRecord
{
    protected static string $resource = GudangResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
