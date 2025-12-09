<x-filament-panels::page>
    <div class="mb-6">
        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$activeTab === 'kode_akun'"
                wire:click.prevent="$set('activeTab', 'kode_akun')"
                tag="button"
            >
                Kode Akun
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'jenis_akun'"
                wire:click.prevent="$set('activeTab', 'jenis_akun')"
                tag="button"
            >
                Jenis Akun
            </x-filament::tabs.item>
        </x-filament::tabs>
    </div>

    <div>
        @if ($activeTab === 'kode_akun')
            @livewire(\App\Filament\Resources\Akunting\KodeAkunResource\Pages\ListKodeAkuns::class)
        @else
            @livewire(\App\Filament\Resources\Akunting\JenisAkunResource\Pages\ListJenisAkun::class)
        @endif
    </div>
</x-filament-panels::page>
