<x-filament-panels::page>
    {{-- Use Chatify package view directly --}}
    @include('Chatify::pages.app', [
        'id' => 0,
        'messengerColor' => $messengerColor,
        'dark_mode' => $darkMode,
    ])
</x-filament-panels::page>
