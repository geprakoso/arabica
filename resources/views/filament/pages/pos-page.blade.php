<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" color="primary">
                Checkout & Print
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
