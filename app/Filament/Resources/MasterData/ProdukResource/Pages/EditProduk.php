<?php

namespace App\Filament\Resources\MasterData\ProdukResource\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduk extends EditRecord
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['nama_produk'] = strtoupper($data['nama_produk']);
        return $data;
    }

}
