<div
    x-data="{ shown: false }"
    x-init="requestAnimationFrame(() => { shown = true })"
    x-show="shown"
    x-transition.opacity.duration.200ms
    class="transition"
>
    <div wire:loading.flex class="items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
        Memuat detail beban...
    </div>
    <div wire:loading.remove>
        @include('filament.infolists.beban-table', [
            'rows' => $this->rows,
            'totalNominal' => $this->totalNominal,
        ])
    </div>
</div>
