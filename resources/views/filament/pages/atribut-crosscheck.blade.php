<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTab === 'crosscheck'"
            wire:click="$set('activeTab', 'crosscheck')"
            icon="heroicon-m-clipboard-document-check"
        >
            Crosscheck
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'list_aplikasi'"
            wire:click="$set('activeTab', 'list_aplikasi')"
            icon="heroicon-m-window"
        >
            List Aplikasi
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'list_os'"
            wire:click="$set('activeTab', 'list_os')"
            icon="heroicon-m-cpu-chip"
        >
            List OS
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTab === 'list_game'"
            wire:click="$set('activeTab', 'list_game')"
            icon="heroicon-m-puzzle-piece"
        >
            List Game
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div>
        @if ($activeTab === 'crosscheck')
            @livewire(\App\Filament\Widgets\AtributCrosscheck\CrosscheckTable::class)
        @elseif ($activeTab === 'list_aplikasi')
            @livewire(\App\Filament\Widgets\AtributCrosscheck\ListAplikasiTable::class)
        @elseif ($activeTab === 'list_os')
            @livewire(\App\Filament\Widgets\AtributCrosscheck\ListOsTable::class)
        @elseif ($activeTab === 'list_game')
            @livewire(\App\Filament\Widgets\AtributCrosscheck\ListGameTable::class)
        @endif
    </div>
</x-filament-panels::page>
