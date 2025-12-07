{{-- resources/views/filament/widgets/absensi-widget.blade.php --}}

<x-filament-widgets::widget>
    <div @class([
        'fi-w-absensi-widget rounded-xl bg-white shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6',
        'bg-yellow-50/70 ring-yellow-200 dark:bg-yellow-900/20 dark:ring-yellow-500/30' => strtolower($currentAbsensi?->status ?? '') === 'izin',
        'bg-red-50/70 ring-red-200 dark:bg-red-900/20 dark:ring-red-500/30' => strtolower($currentAbsensi?->status ?? '') === 'sakit',
    ])>
        
        {{-- LAYOUT UTAMA: Flex Column --}}
        {{-- Match padding with welcome widget (p-6) --}}
        <div class="flex flex-col gap-6">
            
            {{-- BAGIAN ATAS: INFORMASI --}}
            <div class="flex items-start gap-5">
                
                @php
                    $userName = Auth::user()->name ?? 'Pengguna';
                    $statusRaw = $currentAbsensi?->status ?? 'Belum Absen';
                    $statusKey = strtolower($statusRaw);

                    $statusMap = [
                        'hadir' => ['color' => 'primary', 'icon' => 'heroicon-m-check-circle', 'bg' => 'bg-primary-50', 'text' => 'text-primary-600'],
                        'izin' => ['color' => 'warning', 'icon' => 'heroicon-m-document-text', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-600'],
                        'sakit' => ['color' => 'danger', 'icon' => 'heroicon-m-face-frown', 'bg' => 'bg-red-50', 'text' => 'text-red-600'],
                        'belum absen' => ['color' => 'gray', 'icon' => 'heroicon-m-clock', 'bg' => 'bg-gray-50', 'text' => 'text-gray-500'],
                    ];

                    $meta = $statusMap[$statusKey] ?? $statusMap['belum absen'];
                @endphp

                {{-- Teks Info --}}
                <div class="flex flex-col space-y-1 w-full">
                    <h2 class="text-lg md:text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Absensi
                    </h2>

                    @if ($currentAbsensi)
                        @if (strtolower($currentAbsensi->status) === 'hadir')
                            <div class="space-y-3 pt-1">
                                <p class="text-sm md:text-base font-medium text-gray-600 dark:text-gray-300">
                                    Status Anda: <span class="text-primary-600 dark:text-primary-400 font-bold">Hadir</span>
                                </p>
                                
                                {{-- Detail Waktu (Pills) --}}
                                <div class="flex flex-wrap gap-2">
                                    @if ($currentAbsensi->jam_masuk)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700">
                                            @svg('heroicon-m-arrow-right-end-on-rectangle', 'h-3.5 w-3.5 text-primary-500')
                                            <span>Masuk: {{ \Illuminate\Support\Str::of($currentAbsensi->jam_masuk)->substr(0, 5) }}</span>
                                        </div>
                                    @endif
                                    @if ($currentAbsensi->jam_keluar)
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-700">
                                            @svg('heroicon-m-arrow-left-start-on-rectangle', 'h-3.5 w-3.5 text-success-500')
                                            <span>Pulang: {{ \Illuminate\Support\Str::of($currentAbsensi->jam_keluar)->substr(0, 5) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="pt-1">
                                <p class="text-sm md:text-base text-gray-700 dark:text-gray-300">
                                    Anda sedang <span class="font-bold lowercase">{{ $currentAbsensi->status }}</span>.
                                </p>
                                @if($currentAbsensi->keterangan)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 italic mt-1">
                                        "{{ $currentAbsensi->keterangan }}"
                                    </p>
                                @endif
                            </div>
                        @endif
                    @else
                        <p class="text-sm md:text-base text-gray-500 dark:text-gray-400 pt-1">
                            Belum ada aktivitas absensi hari ini.
                        </p>
                    @endif
                </div>
            </div>

            {{-- BAGIAN BAWAH: TOMBOL AKSI --}}
            {{-- Horizontal Line Dihapus. --}}
            {{-- Desktop: Aligned Right. Mobile: Full Width. --}}
            <div class="flex items-center justify-end">
                <div class="w-full md:w-auto">
                    @if (!$currentAbsensi)
                        <x-filament::button
                            tag="a"
                            href="{{ \App\Filament\Resources\Absensi\AbsensiResource::getUrl('create') }}"
                            color="primary"
                            size="lg"
                            icon="heroicon-m-play"
                            class="w-full md:w-auto shadow-sm"
                        >
                            Absen Masuk
                        </x-filament::button>

                    @elseif (strtolower($currentAbsensi->status) === 'hadir' && !$currentAbsensi->jam_keluar)
                        <x-filament::button
                            wire:click="checkOut"
                            color="success" 
                            size="lg"
                            icon="heroicon-m-stop"
                            class="w-full md:w-auto shadow-sm"
                        >
                            Absen Pulang
                        </x-filament::button>

                    @else
                        {{-- Status Badge Selesai/Final --}}
                        <div class="flex items-center justify-center gap-2 px-6 py-2 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 text-sm font-medium w-full md:w-auto ring-1 ring-gray-200 dark:ring-gray-700 cursor-default">
                            @svg('heroicon-m-check-badge', 'h-5 w-5')
                            <span>Absensi Selesai</span>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-filament-widgets::widget>
