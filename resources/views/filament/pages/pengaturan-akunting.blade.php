{{-- Root page wrapper provided by Filament --}}
<x-filament-panels::page>
    {{-- Container for the tab navigation --}}
    <div class="mb-6">
        {{-- Filament tabs wrapper --}}
        <x-filament::tabs>
            {{-- Tab item for Kode Akun --}}
            <x-filament::tabs.item
                {{-- Active state when Livewire property equals kode_akun --}}
                :active="$activeTab === 'kode_akun'"
                {{-- Livewire update to switch tab state --}}
                wire:click.prevent="$set('activeTab', 'kode_akun')"
                {{-- Push kode_akun into the URL without reloading --}}
                x-on:click="window.history.replaceState({}, '', '{{ request()->fullUrlWithQuery(['activeTab' => 'kode_akun']) }}')"
                {{-- Render as button element --}}
                tag="button"
                {{-- Badge jumlah record Kode Akun --}}
                :badge="$kodeAkunCount"
                badge-color="gray"
            >
                {{-- Visible label for this tab --}}
                Kode Akun
            </x-filament::tabs.item>

            {{-- Tab item for Jenis Akun --}}
            <x-filament::tabs.item
                {{-- Active state when Livewire property equals jenis_akun --}}
                :active="$activeTab === 'jenis_akun'"
                {{-- Livewire update to switch tab state --}}
                wire:click.prevent="$set('activeTab', 'jenis_akun')"
                {{-- Push jenis_akun into the URL without reloading --}}
                x-on:click="window.history.replaceState({}, '', '{{ request()->fullUrlWithQuery(['activeTab' => 'jenis_akun']) }}')"
                {{-- Render as button element --}}
                tag="button"
                {{-- Badge jumlah record Jenis Akun --}}
                :badge="$jenisAkunCount"
                badge-color="gray"
            >
                {{-- Visible label for this tab --}}
                Jenis Akun
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    {{-- Content container that swaps per tab --}}
    <div>
        {{-- Show Kode Akun list when tab matches --}}
        @if ($activeTab === 'kode_akun')
            {{-- Livewire component for Kode Akun list --}}
            @livewire(\App\Filament\Resources\Akunting\KodeAkunResource\Pages\ListKodeAkuns::class)
        @else
            {{-- Livewire component for Jenis Akun list --}}
            @livewire(\App\Filament\Resources\Akunting\JenisAkunResource\Pages\ListJenisAkun::class)
        @endif
    </div>
</x-filament-panels::page>
