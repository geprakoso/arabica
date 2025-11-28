<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjadwalanService extends CreateRecord
{
    protected static string $resource = PenjadwalanServiceResource::class;

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
