<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjadwalanTugas extends CreateRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

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
    //     return [];php 
    // }
}
