<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ContentCalendarView extends Page
{
    protected static string $view = 'filament.pages.content-calendar-view';

    protected static ?string $title = 'Calendar View';

    protected static ?string $navigationGroup = 'Konten Sosmed';

    protected static ?string $navigationLabel = 'Calendar View';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = -6;

    protected static ?string $slug = 'konten-sosmed/calendar-view';
}
