@php
    $summary = $this->statusSummary;
    $statusOptions = [
        'hadir' => 'Hadir',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
    ];
    $statusBadgeColors = [
        'hadir' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
        'izin' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
        'sakit' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200',
        'default' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    ];
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        Filter Data Absensi
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Sesuaikan rentang tanggal, karyawan, dan status (hadir, izin, sakit) sesuai kebutuhan laporan.
                    </p>
                </div>

                <x-filament::button color="gray" icon="heroicon-m-arrow-path" wire:click="resetFilters">
                    Reset Filter
                </x-filament::button>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Mulai Tanggal</label>
                    <input
                        type="date"
                        wire:model.live="mulaiTanggal"
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input
                        type="date"
                        wire:model.live="sampaiTanggal"
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Karyawan</label>
                    <select
                        wire:model.live="karyawanFilter"
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option value="">Semua Karyawan</option>
                        @foreach ($this->employeeOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select
                        wire:model.live="statusFilter"
                        class="fi-input block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-advanced-widgets::section
                heading="Total Hadir"
                description="Jumlah karyawan berstatus hadir pada periode ini."
                icon="heroicon-m-check-badge"
                icon-color="success"
                icon-background-color="bg-emerald-100 dark:bg-emerald-500/10"
                :compact="true"
            >
                <p class="text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($summary['hadir']) }}
                </p>
            </x-advanced-widgets::section>

            <x-advanced-widgets::section
                heading="Total Izin"
                description="Pengajuan izin yang disetujui selama rentang tanggal."
                icon="heroicon-m-document-check"
                icon-color="warning"
                icon-background-color="bg-amber-100 dark:bg-amber-500/10"
                :compact="true"
            >
                <p class="text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($summary['izin']) }}
                </p>
            </x-advanced-widgets::section>

            <x-advanced-widgets::section
                heading="Total Sakit"
                description="Karyawan yang melaporkan status sakit."
                icon="heroicon-m-heart"
                icon-color="danger"
                icon-background-color="bg-rose-100 dark:bg-rose-500/10"
                :compact="true"
            >
                <p class="text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($summary['sakit']) }}
                </p>
            </x-advanced-widgets::section>

            <x-advanced-widgets::section
                heading="Total Data"
                description="Keseluruhan catatan absensi sesuai filter."
                icon="heroicon-m-clipboard-document-check"
                icon-color="info"
                icon-background-color="bg-sky-100 dark:bg-sky-500/10"
                :compact="true"
            >
                <p class="text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ number_format($summary['total']) }}
                </p>
                @if ($summary['lainnya'] > 0)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Termasuk {{ $summary['lainnya'] }} status lain (mis. Alpha).
                    </p>
                @endif
            </x-advanced-widgets::section>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-advanced-widgets::section
                heading="Ringkasan Per Karyawan"
                description="Rekap jumlah hadir, izin, dan sakit untuk setiap karyawan."
                icon="heroicon-m-users"
                icon-color="primary"
                icon-background-color="bg-slate-100 dark:bg-slate-500/10"
            >
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Total karyawan</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $this->userSummaries->count() }}
                    </span>
                </div>

                <div class="mt-4 -mx-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-2">Karyawan</th>
                                <th class="px-4 py-2 text-center">Hadir</th>
                                <th class="px-4 py-2 text-center">Izin</th>
                                <th class="px-4 py-2 text-center">Sakit</th>
                                <th class="px-4 py-2 text-center">Lainnya</th>
                                <th class="px-4 py-2 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($this->userSummaries as $row)
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ $row->user->name ?? 'Tidak diketahui' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center text-emerald-600 dark:text-emerald-300">
                                        {{ $row->total_hadir }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-amber-600 dark:text-amber-300">
                                        {{ $row->total_izin }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-rose-600 dark:text-rose-300">
                                        {{ $row->total_sakit }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                        {{ $row->total_lainnya }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $row->total_absen }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-4 text-center text-gray-500 dark:text-gray-400" colspan="6">
                                        Tidak ada data absensi untuk filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-advanced-widgets::section>

            <x-advanced-widgets::section
                heading="Rincian Absensi"
                description="Daftar absensi sesuai filter, lengkap dengan jam masuk & pulang."
                icon="heroicon-m-clipboard-document"
                icon-color="success"
                icon-background-color="bg-emerald-100 dark:bg-emerald-500/10"
            >
                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                    <span>Total baris</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                        {{ $this->absensiRecords->count() }}
                    </span>
                </div>

                <div class="mt-4 -mx-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                <th class="px-4 py-2">Tanggal</th>
                                <th class="px-4 py-2">Karyawan</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Jam Masuk</th>
                                <th class="px-4 py-2">Jam Keluar</th>
                                <th class="px-4 py-2">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($this->absensiRecords as $absensi)
                                @php
                                    $statusKey = strtolower((string) ($absensi->status ?? ''));
                                    $badgeColor = $statusBadgeColors[$statusKey] ?? $statusBadgeColors['default'];
                                @endphp
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="px-4 py-3">
                                        {{ $absensi->tanggal ? \Illuminate\Support\Carbon::parse($absensi->tanggal)->translatedFormat('d M Y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $absensi->user->name ?? 'Tidak diketahui' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeColor }}">
                                            {{ \Illuminate\Support\Str::of($absensi->status ?? 'Tidak diketahui')->headline() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $absensi->jam_masuk ? \Illuminate\Support\Str::of($absensi->jam_masuk)->substr(0, 5) : '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $absensi->jam_keluar ? \Illuminate\Support\Str::of($absensi->jam_keluar)->substr(0, 5) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $absensi->keterangan ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-4 text-center text-gray-500 dark:text-gray-400" colspan="6">
                                        Belum ada data absensi yang cocok dengan filter saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-advanced-widgets::section>
        </div>
    </div>
</x-filament-panels::page>
