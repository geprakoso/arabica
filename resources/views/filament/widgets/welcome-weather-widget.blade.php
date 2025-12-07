<x-filament-widgets::widget>
    {{-- Card luar untuk widget --}}
    <div class="fi-w-welcome-weather-widget rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        
        <div class="flex items-center justify-between space-x-4">
            {{-- Bagian Kiri: Welcome Message --}}
            <div class="space-y-1">
                <h2 class="text-3xl font-extrabold tracking-tight text-gray-950 dark:text-white">
                    Halo, {{ $userName }}!
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-lg">
                    Semoga hari Anda produktif!
                </p>
            </div>

            {{-- Bagian Kanan: Weather Widget --}}
            <div class="flex items-center space-x-3 bg-primary-50 dark:bg-primary-900/50 rounded-lg p-4 transition duration-300 ease-in-out hover:bg-primary-100 dark:hover:bg-primary-900">
                
                {{-- Icon Cuaca --}}
                @if ($weather)
                    @php
                        // Gunakan icon yang sudah ada di Heroicons (bawaan Filament)
                        $iconClass = 'h-10 w-10 text-primary-600 dark:text-primary-400';
                        $iconName = $weather['icon'];
                    @endphp

                    {{-- Render Heroicon --}}
                    @svg($iconName, $iconClass)
                
                    {{-- Detail Cuaca --}}
                    <div class="space-y-0 text-right">
                        <p class="text-xl font-bold text-gray-950 dark:text-white leading-none">
                            {{ $weather['temp'] }}°C
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 leading-none">
                            {{ $weather['condition'] }}
                        </p>
                        <p class="text-xs font-medium text-primary-600 dark:text-primary-400 leading-none mt-1">
                            di {{ $weather['city'] }}
                        </p>
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Cuaca tidak tersedia.
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>