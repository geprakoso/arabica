<?php

namespace App\Filament\Resources\Absensi\LemburResource\Pages;

use App\Filament\Resources\Absensi\LemburResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLembur extends CreateRecord
{
    protected static string $resource = LemburResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         $this->getCreateFormAction(),
    //         $this->getCreateAnotherFormAction(),
    //         $this->getCancelFormAction(),
    //     ];
    // }

    // // protected function getFormActions(): array
    // // {
    // //     // Pindahkan tombol ke header agar footer lebih bersih.
    // //     return [];
    // // }
}
