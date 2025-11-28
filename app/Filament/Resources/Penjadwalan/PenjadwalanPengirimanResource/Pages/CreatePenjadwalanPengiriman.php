<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjadwalanPengiriman extends CreateRecord
{
    protected static string $resource = PenjadwalanPengirimanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        // Pindahkan tombol ke header agar footer lebih bersih.
        return [];
    }
}
