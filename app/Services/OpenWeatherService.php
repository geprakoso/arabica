<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenWeatherService
{
    public function getCurrentWeather(bool $forceRefresh = false): ?array
    {
        $apiKey = config('services.openweather.key');
        $city = config('services.openweather.city');
        $units = config('services.openweather.units', 'metric');
        $cacheTtl = (int) config('services.openweather.cache', 15);

        if (blank($apiKey) || blank($city)) {
            return null;
        }

        $cacheKey = sprintf('openweather.current.%s.%s', Str::slug($city), $units);

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes($cacheTtl), function () use ($apiKey, $city, $units) {
            $response = Http::timeout(8)->get('https://api.openweathermap.org/data/2.5/weather', [
                'q' => $city,
                'appid' => $apiKey,
                'units' => $units,
                'lang' => str_replace('_', '-', app()->getLocale()),
            ]);

            if ($response->failed()) {
                if (config('app.debug')) {
                    logger()->warning('Failed to fetch OpenWeather data', [
                        'city' => $city,
                        'body' => $response->body(),
                    ]);
                }

                return null;
            }

            // Parse and return relevant data
            $data = $response->json();

            return [
                'location' => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'] ?? null,
                'feels_like' => $data['main']['feels_like'] ?? null,
                'humidity' => $data['main']['humidity'] ?? null,
                'wind_speed' => $data['wind']['speed'] ?? null,
                'description' => isset($data['weather'][0]['description'])
                    ? Str::title($data['weather'][0]['description'])
                    : null,
                'icon' => $data['weather'][0]['icon'] ?? null,
                'iconcolor' => isset($data['weather'][0]['icon'])
                    ? (Str::endsWith($data['weather'][0]['icon'], 'd') ? 'yellow' : 'blue')
                    : null,
                'sunrise' => isset($data['sys']['sunrise']) ? (int) $data['sys']['sunrise'] : null,
                'sunset' => isset($data['sys']['sunset']) ? (int) $data['sys']['sunset'] : null,
                'timezone' => $data['timezone'] ?? 0,
                'retrieved_at' => now(),
                'units' => $units,
            ];
        });
    }
}
