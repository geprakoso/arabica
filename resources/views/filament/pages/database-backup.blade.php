<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">
                Export Database
            </x-slot>

            <x-slot name="description">
                Download seluruh data aplikasi dalam format SQL.
            </x-slot>

            <div class="flex flex-col items-center justify-center p-6 space-y-4">
                <p class="text-sm text-center text-gray-500">
                    Klik tombol di bawah ini untuk mengunduh backup database terbaru.
                    File ini berisi seluruh data pengguna, pengaturan, dan transaksi.
                </p>
                
                <div class="flex flex-col items-center gap-2">
                    <x-filament::button wire:click="export" color="primary" icon="heroicon-o-arrow-down-tray" 
                        wire:loading.attr="disabled" wire:target="export">
                        <span wire:loading.remove wire:target="export">Export Database</span>
                        <span wire:loading wire:target="export">Memproses...</span>
                    </x-filament::button>

                    <div wire:loading wire:target="export" class="text-xs text-primary-600 animate-pulse">
                        Sedang menyiapkan file backup...
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                Import Database
            </x-slot>

            <x-slot name="description">
                Restore data dari file backup SQL.
            </x-slot>
            
            <form wire:submit="import" class="space-y-4">
                {{ $this->form }}
                
                <div class="flex flex-col items-end gap-2">
                    <x-filament::button type="submit" color="danger" icon="heroicon-o-arrow-up-tray"
                        wire:confirm="Apakah Anda yakin ingin melakukan restore database? Data yang ada saat ini akan ditimpa dan tidak bisa dikembalikan."
                        wire:loading.attr="disabled" wire:target="import">
                        <span wire:loading.remove wire:target="import">Import Database</span>
                        <span wire:loading wire:target="import">Memproses...</span>
                    </x-filament::button>

                    <div wire:loading wire:target="import" class="text-xs text-danger-600 animate-pulse">
                        Sedang me-restore database, mohon tunggu...
                    </div>
                </div>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
