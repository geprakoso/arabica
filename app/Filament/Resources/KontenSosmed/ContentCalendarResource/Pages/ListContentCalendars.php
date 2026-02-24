<?php

namespace App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

use App\Filament\Resources\KontenSosmed\ContentCalendarResource;
use App\Models\ContentCalendar;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets;

class ListContentCalendars extends ListRecords
{
    protected static string $resource = ContentCalendarResource::class;

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\CreateAction::make()
    //             ->label('Buat Konten')
    //             ->icon('heroicon-m-plus')
    //             ->color('primary')
    //             ->modalHeading('Buat Konten Baru'),
    //     ];
    // }

    protected function getHeaderWidgets(): array
    {
        return [
            ContentCalendarStatsWidget::class,
        ];
    }
}
