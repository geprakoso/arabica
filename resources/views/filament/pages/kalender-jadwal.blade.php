<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    {{-- <x-filament::section>
        <div class="space-y-2 text-sm text-gray-600">
            <div class="font-semibold text-gray-700">Cara Pakai</div>
            <div>Klik event untuk lihat detail.</div>
            <div>Drag di kalender untuk membuat tugas/event.</div>
            <div>Gunakan tombol “Buat Tugas” atau “Buat Event” di header kalender.</div>
        </div>
    </x-filament::section> --}}

    <style>
        .ec {
            --ec-border-color: rgba(148, 163, 184, 0.35);
            --ec-today-bg-color: rgba(59, 130, 246, 0.08);
            --ec-event-text-color: #ffffff;
        }

        .ec .ec-day.ec-today {
            background-color: var(--ec-today-bg-color);
        }

        .ec .ec-header .ec-toolbar {
            border-radius: 12px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(16, 185, 129, 0.08));
            padding: 8px 12px;
        }

        .ec .ec-event {
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }
    </style>
</x-filament-panels::page>
