<?php

namespace App\Filament\Widgets;

use App\Services\OpenWeatherService;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class OpenWeatherWidget extends Widget
{
    protected static string $view = 'filament.widgets.open-weather-widget';

    protected static ?string $heading = 'Cuaca Saat Ini';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
    ];

    protected static ?int $sort = -9;

    public ?array $weather = null;

    public function mount(OpenWeatherService $weatherService): void
    {
        $this->weather = $weatherService->getCurrentWeather();
    }

    public function refreshWeather(OpenWeatherService $weatherService): void
    {
        $this->weather = $weatherService->getCurrentWeather(forceRefresh: true);
    }

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos';
    }
}
