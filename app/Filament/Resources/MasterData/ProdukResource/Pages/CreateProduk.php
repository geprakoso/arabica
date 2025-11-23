<?php

namespace App\Filament\Resources\MasterData\ProdukResource\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduk extends CreateRecord
{
    protected static string $resource = ProdukResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['nama_produk'] = strtoupper($data['nama_produk']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
