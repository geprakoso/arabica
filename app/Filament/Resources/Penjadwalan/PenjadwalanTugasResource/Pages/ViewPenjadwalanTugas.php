<?php

namespace App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource\Pages;

use App\Filament\Resources\Penjadwalan\PenjadwalanTugasResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPenjadwalanTugas extends ViewRecord
{
    protected static string $resource = PenjadwalanTugasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
