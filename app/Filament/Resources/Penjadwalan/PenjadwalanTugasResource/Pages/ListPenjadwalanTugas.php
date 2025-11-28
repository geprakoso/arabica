<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenjadwalanTugas extends ListRecords
{
    protected static string $resource = PenjadwalanTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
