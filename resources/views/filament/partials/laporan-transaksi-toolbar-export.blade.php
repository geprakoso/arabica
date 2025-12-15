<div class="flex items-center" wire:key="laporan-transaksi-toolbar-export">
    <x-filament::button
        size="sm"
        color="gray"
        icon="heroicon-o-arrow-down-tray"
        wire:click="mountTableAction('export')"
    >
        Export
    </x-filament::button>
</div>
