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

    // Mengatur ukuran kolom untuk widget (opsional, bisa 1/2, 1/3, atau full)
    // Kita buat dia mengambil 2 kolom dari total 3 kolom agar terlihat menonjol
    protected int | string | array $columnSpan = '1/2';

    public $weatherData = null;
    public $userName = '';
    
    protected function setUp(): void
    {
        // Panggil fungsi untuk mengambil data cuaca saat widget dimuat
        $this->fetchWeather();
    }
    
    // Metode untuk mengambil data cuaca dari API eksternal (Contoh: OpenWeatherMap)
    protected function fetchWeather(): void
    {
        // --- Perhatian: Anda perlu mendapatkan API Key dari layanan cuaca ---
        // Untuk contoh, saya akan menggunakan data statis/dummy.
        // Jika Anda ingin mengintegrasikannya, Anda perlu API seperti OpenWeatherMap, AccuWeather, dll.
        // Pastikan Anda menyimpan API_KEY di file .env.
        
        $city = 'Kudus'; // Lokasi yang diminta
        $temp = '28'; // Suhu dummy
        $condition = 'Cerah Berawan'; // Kondisi dummy
        $icon = 'sun'; // Icon dummy, kita akan gunakan icon dari Blade Icons

        // **Contoh Integrasi API Cuaca (Jika Anda sudah memiliki API Key)**
        $apiKey = env('OPENWEATHER_API_KEY');
        $url = "https://api.openweathermap.org/data/3.0/weather?q={$city}&appid={$apiKey}&units=metric";
        
        // try {
        //     $response = Http::get($url)->json();
        //     $temp = round($response['main']['temp']);
        //     $condition = $response['weather'][0]['description'];
        //     // Logic untuk menentukan icon dari OpenWeatherMap
        // } catch (Exception $e) {
        //     // Handle error atau gunakan data dummy jika gagal
        // }

        $this->weatherData = [
            'city' => $city,
            'temp' => $temp,
            'condition' => $condition,
            'icon' => 'heroicon-o-sun', // Menggunakan Heroicon bawaan Filament
        ];

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
