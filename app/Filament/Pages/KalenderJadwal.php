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

class KalenderJadwal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Kalender Jadwal';
    protected static ?string $title = 'Kalender Jadwal';
    protected static ?int $navigationSort = -1;
    protected static string $view = 'filament.pages.kalender-jadwal';

    public ?array $filters = [];

    public function mount(): void
    {
        $now = Carbon::now();

        $this->form->fill([
            'month' => (int) $now->month,
            'year' => (int) $now->year,
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('month')
                    ->label('Bulan')
                    ->options($this->monthOptions())
                    ->native(false)
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updateCalendarDate()),
                Select::make('year')
                    ->label('Tahun')
                    ->options($this->yearOptions())
                    ->native(false)
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->updateCalendarDate()),
            ])
            ->columns(2)
            ->statePath('filters');
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getFooterWidgets(): array
    {
        return [
            JadwalKalenderWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | string | array
    {
        return 1;
    }

    private function updateCalendarDate(): void
    {
        $month = (int) ($this->filters['month'] ?? 0);
        $year = (int) ($this->filters['year'] ?? 0);

        if ($month < 1 || $month > 12 || $year < 1) {
            return;
        }

        $this->dispatch('calendar-date-set', date: sprintf('%04d-%02d-01', $year, $month));
    }

    private function monthOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }

    private function yearOptions(): array
    {
        $currentYear = (int) Carbon::now()->year;
        $years = range($currentYear - 3, $currentYear + 3);

        return array_combine($years, $years);
    }
}
