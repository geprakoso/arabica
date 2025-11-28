<?php

namespace App\Filament\Resources\PosActivityResource\Pages;

use App\Filament\Resources\PosActivityResource;
use App\Filament\Resources\PosActivityResource\Widgets\PosActivityStats;
use Filament\Resources\Pages\ListRecords;

class ListPosActivities extends ListRecords
{
    protected static string $resource = PosActivityResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            PosActivityStats::class,
        ];
    }
}
