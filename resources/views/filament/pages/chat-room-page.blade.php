@php
    $chatifyUrl = route(config('chatify.routes.prefix'));
@endphp

<x-filament-panels::page class="fi-chat-redirect">
    <div class="fi-chat-redirect__card">
        <h2>Opening Chatify…</h2>
        <p>
            Your chat inbox opens in a dedicated tab.
            If it doesn't open automatically,
            <a href="{{ $chatifyUrl }}" target="_blank" rel="noopener">click here</a>.
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const url = @js($chatifyUrl);
            const popup = window.open(url, '_blank', 'noopener');

            if (!popup) {
                window.location.href = url;
            }
        });
    </script>
</x-filament-panels::page>
