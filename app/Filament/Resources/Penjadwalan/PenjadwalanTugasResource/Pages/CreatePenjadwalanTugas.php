<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjadwalanTugas extends CreateRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Filament::auth()->id();

        return $data;
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         $this->getCreateFormAction(),
    //         $this->getCreateAnotherFormAction(),
    //         $this->getCancelFormAction(),
    //     ];
    // }

    // protected function getFormActions(): array
    // {
    //     // Pindahkan tombol ke header agar footer lebih bersih.
    //     return [];
    // }
}
