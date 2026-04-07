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

        $thisMonth = ContentCalendar::whereMonth('tanggal_publish', now()->month)
            ->whereYear('tanggal_publish', now()->year)
            ->count();

        return [
            Stat::make('Total Konten', $total)
                ->description('Semua konten di sistem')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('primary'),
            Stat::make('Bulan Ini', $thisMonth)
                ->description('Total jadwal bulan ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->chart([4, 6, 8, 3, 5, 9, 12])
                ->color('info'),
            Stat::make('Published', $published)
                ->description('Konten sudah tayang')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([1, 3, 2, 5, 8, 4, 10])
                ->color('success'),
            Stat::make('Scheduled', $scheduled)
                ->description('Menunggu jadwal tayang')
                ->descriptionIcon('heroicon-m-clock')
                ->chart([2, 5, 3, 7, 6, 8, 5])
                ->color('primary'),
            Stat::make('Waiting Approval', $waiting)
                ->description('Perlu direview PIC')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->chart([5, 4, 6, 3, 7, 5, 6])
                ->color('warning'),
            Stat::make('Draft', $draft)
                ->description('Masih tahap konsep')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->chart([8, 6, 9, 5, 7, 4, 3])
                ->color('gray'),
        ];
    }
}
