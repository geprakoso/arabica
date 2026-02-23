<?php

namespace App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

use App\Models\ContentCalendar;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentCalendarStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $total = ContentCalendar::count();
        $published = ContentCalendar::where('status', 'published')->count();
        $waiting = ContentCalendar::where('status', 'waiting')->count();
        $scheduled = ContentCalendar::where('status', 'scheduled')->count();
        $draft = ContentCalendar::where('status', 'draft')->count();

        return [
            Stat::make('Total Konten', $total)
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make('Published', $published)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Scheduled', $scheduled)
                ->icon('heroicon-o-calendar')
                ->color('info'),
            Stat::make('Waiting', $waiting)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Draft', $draft)
                ->icon('heroicon-o-pencil-square')
                ->color('gray'),
        ];
    }
}
