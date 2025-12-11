<?php

namespace App\Filament\Resources\MasterData\BrandResource\Pages;

use App\Filament\Resources\MasterData\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function afterCreate(): void
    {
        session()->flash('success', 'Brand berhasil dibuat.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
