@php
    $weather = $this->weather;
    $unitSymbol = ($weather['units'] ?? 'metric') === 'imperial' ? '°F' : '°C';
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                    Cuaca Saat Ini
                </h3>
                <p class="text-sm text-gray-500">
                    {{ $weather['location'] ?? config('services.openweather.city') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    color="gray"
                    size="sm"
                    wire:click="refreshWeather"
                    wire:loading.attr="disabled"
                    icon="heroicon-m-arrow-path"
                    class="shrink-0"
                >
                    Refresh
                </x-filament::button>
            </div>
        </div>

        <div class="mt-4">
            @if ($weather)
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-3">
                        @if ($weather['icon'] ?? false)
                            <img
                                src="https://openweathermap.org/img/wn/{{ $weather['icon'] }}@2x.png"
                                alt="{{ $weather['description'] }}"
                                class="h-16 w-16"
                            />
                        @endif

                        <div>
                            <div class="text-4xl font-bold">
                                {{ round($weather['temperature']) }}{{ $unitSymbol }}
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $weather['description'] ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid gap-4 text-sm text-gray-600 dark:text-gray-300 sm:grid-cols-3">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">Terasa</dt>
                            <dd>{{ round($weather['feels_like']) }}{{ $unitSymbol }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">Kelembapan</dt>
                            <dd>{{ $weather['humidity'] ?? '—' }}%</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">Angin</dt>
                            <dd>{{ $weather['wind_speed'] ?? '—' }} {{ ($weather['units'] ?? 'metric') === 'imperial' ? 'mph' : 'm/s' }}</dd>
                        </div>
                    </dl>
                </div>

                <p class="mt-4 text-xs text-gray-400">
                    Diperbarui {{ $weather['retrieved_at']?->timezone(config('app.timezone'))->diffForHumans() }}
                </p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Data cuaca belum tersedia. Pastikan variabel <code>OPENWEATHER_API_KEY</code> dan <code>OPENWEATHER_CITY</code> sudah diisi.
                </p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
