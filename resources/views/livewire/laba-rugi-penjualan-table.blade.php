<div
    x-data="{ shown: false }"
    x-init="requestAnimationFrame(() => { shown = true })"
    x-show="shown"
    x-transition.opacity.duration.200ms
    class="transition"
>
    <div wire:loading.flex class="items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 p-6 text-sm text-gray-500">
        Memuat daftar produk terjual...
    </div>
    <div wire:loading.remove>
        @include('filament.infolists.penjualan-items-table', ['rows' => $this->rows])
    </div>
</div>
