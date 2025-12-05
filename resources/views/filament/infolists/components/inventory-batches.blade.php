@php
    $batches = $getState() ?? [];
@endphp

<div class="space-y-4">
    @forelse ($batches as $batch)
        <div class="rounded-xl border border-gray-200/80 bg-white/90 p-4 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900/40 dark:ring-white/10">
            <div class="flex items-center justify-between gap-4 border-b border-dashed border-gray-200 pb-3 text-sm font-medium text-gray-700 dark:border-white/10 dark:text-gray-200">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">No. PO</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $batch['no_po'] ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tanggal</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $batch['tanggal'] ?? '-' }}</p>
                </div>
            </div>

            <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-3 text-sm justify-between">
                <div class="space-y-1 min-w-[90px] flex-none">
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                        {{ number_format($batch['qty'] ?? 0, 0, ',', '.') }}
                    </dd>
                </div>
                <div class="space-y-1 min-w-[200px] flex-none">
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">HPP</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                        {{ $batch['hpp_display'] ?? '-' }}
                    </dd>
                </div>
                <div class="space-y-1 min-w-[200px] flex-none">
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Harga Jual</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                        {{ $batch['harga_jual_display'] ?? '-' }}
                    </dd>
                </div>
                <div class="space-y-1 min-w-[200px] flex-none">
                    <dt class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Kondisi</dt>
                    <dd class="mt-1">
                        @php
                            $condition = $batch['kondisi'] ?? null;
                            $badgeMap = [
                                'baru' => 'success',
                                'new' => 'success',
                                'bekas' => 'warning',
                                'refurbished' => 'warning',
                                'rusak' => 'danger',
                            ];
                            $badgeColor = $badgeMap[strtolower($condition ?? '')] ?? 'primary';
                            $conditionLabel = $condition ? strtoupper($condition) : '-';
                        @endphp
                        <x-filament::badge color="{{ $badgeColor }}" size="md" class="font-normal uppercase px-3 py-1 whitespace-nowrap">
                            {{ $conditionLabel }}
                        </x-filament::badge>
                    </dd>
                </div>
            </dl>
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/80 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-gray-900/20 dark:text-gray-400">
            Belum ada batch pembelian aktif.
        </div>
    @endforelse
</div>
