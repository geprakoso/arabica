<?php

namespace App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

use App\Filament\Resources\KontenSosmed\ContentCalendarResource;
use Filament\Resources\Pages\Page;

class CalendarContentCalendar extends Page
{
    protected static string $resource = ContentCalendarResource::class;

    protected static string $view = 'filament.resources.konten-sosmed.calendar-content-calendar';

    protected static ?string $title = 'Kalender Konten';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendar View';

    protected static ?int $navigationSort = 1;

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 1;
    }
}
