@php
    $bulananUrl = \App\Filament\Resources\Akunting\LaporanNeracaResource::getUrl('index');
    $detailUrl = \App\Filament\Pages\NeracaCustom::getUrl();
    $currentUrl = url()->current();
@endphp

<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$currentUrl === $bulananUrl"
                :href="$bulananUrl"
                tag="a"
            >
                Bulanan
            </x-filament::tabs.item>
            <x-filament::tabs.item
                :active="$currentUrl === $detailUrl"
                :href="$detailUrl"
                tag="a"
            >
                Detail
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{ $this->getInfolist('infolist') }}
    </div>
</x-filament-panels::page>
