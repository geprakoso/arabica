<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Exception;

class WelcomeWeatherWidget extends Widget
{
    // Mengatur view custom untuk widget ini
    protected static string $view = 'filament.widgets.welcome-weather-widget';
    protected static ?int $sort = 1;

    // Mengatur ukuran kolom untuk widget (opsional, bisa 1/2, 1/3, atau full)
    // Kita buat dia mengambil 2 kolom dari total 3 kolom agar terlihat menonjol
    protected int | string | array $columnSpan = '1/2';

    public $weatherData = null;
    public $userName = '';
    
    public function mount(): void
    {
        // Panggil fungsi untuk mengambil data cuaca saat widget dimuat
        $this->fetchWeather();
    }
    
    // Metode untuk mengambil data cuaca dari API eksternal (Contoh: OpenWeatherMap)
    protected function fetchWeather(): void
    {
        $city = config('services.openweather.city', 'Kudus');
        $apiKey = config('services.openweather.key');

        try {
            if (blank($apiKey)) {
                $this->weatherData = [
                    'city' => $city,
                    'temp' => '—',
                    'condition' => 'Set OPENWEATHER_API_KEY di .env',
                    'icon' => 'heroicon-o-information-circle',
                ];
                $this->userName = Auth::user()->name ?? 'Pengguna';
                return;
            }

            $response = Http::timeout(5)->get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'id',
            ])->json();

            $temp = round(data_get($response, 'main.temp', 0));
            $condition = ucfirst(data_get($response, 'weather.0.description', ''));

            $this->weatherData = [
                'city' => $city,
                'temp' => $temp,
                'condition' => $condition ?: 'Tidak diketahui',
                'icon' => 'heroicon-o-sun', // Tetap pakai icon heroicon sederhana
            ];
        } catch (Exception $e) {
            // Fallback untuk lingkungan tanpa internet atau API key
            $this->weatherData = [
                'city' => $city,
                'temp' => '—',
                'condition' => 'Cuaca tidak tersedia (offline)',
                'icon' => 'heroicon-o-cloud',
            ];
        }

        // Ambil nama user yang sedang login
        $this->userName = Auth::user()->name ?? 'Pengguna';
    }

    // Mengirimkan data ke view
    protected function getViewData(): array
    {
        return [
            'userName' => $this->userName,
            'weather' => $this->weatherData,
        ];
    }
}
