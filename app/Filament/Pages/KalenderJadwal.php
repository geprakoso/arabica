<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\JadwalKalenderWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class KalenderJadwal extends Page
{
    protected function getFooterWidgets(): array
    {
        return [
            JadwalKalenderWidget::class,
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Kalender Jadwal';
    protected static ?string $title = 'Kalender Jadwal';
    protected static ?string $navigationGroup = 'Kalender';
    protected static ?int $navigationSort = -3;
    protected static string $view = 'filament.pages.kalender-jadwal';

    public function getFooterWidgetsColumns(): int | string | array
    {
        return 1;
    }
}
