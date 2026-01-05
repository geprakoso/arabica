@php
    $eventClickEnabled = $this->isEventClickEnabled();
    $eventDragEnabled = $this->isEventDragEnabled();
    $eventResizeEnabled = $this->isEventResizeEnabled();
    $noEventsClickEnabled = $this->isNoEventsClickEnabled();
    $dateClickEnabled = $this->isDateClickEnabled();
    $dateSelectEnabled = $this->isDateSelectEnabled();
    $datesSetEnabled = $this->isDatesSetEnabled();
    $viewDidMountEnabled = $this->isViewDidMountEnabled();
    $eventAllUpdatedEnabled = $this->isEventAllUpdatedEnabled();
    $onEventResizeStart = method_exists($this, 'onEventResizeStart');
    $onEventResizeStop = method_exists($this, 'onEventResizeStop');
    $hasDateClickContextMenu = !empty($this->getCachedDateClickContextMenuActions());
    $hasDateSelectContextMenu = !empty($this->getCachedDateSelectContextMenuActions());
    $hasEventClickContextMenu = !empty($this->getCachedEventClickContextMenuActions());
    $hasNoEventsClickContextMenu = !empty($this->getCachedNoEventsClickContextMenuActions());

    $dayHeaderFormatJs = $this->getDayHeaderFormatJs();
    $slotLabelFormatJs = $this->getSlotLabelFormatJs();
@endphp

<x-filament-widgets::widget>
    <style>
        .calendar-shell {
            border-radius: 1.25rem;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(34, 197, 94, 0.06));
        }

        .dark .calendar-shell {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(2, 6, 23, 0.8));
        }

        .calendar-hero {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .calendar-title {
            display: grid;
            gap: 0.15rem;
        }

        .calendar-title h2 {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .calendar-title p {
            font-size: 0.85rem;
            color: rgba(71, 85, 105, 1);
        }

        .dark .calendar-title p {
            color: rgba(148, 163, 184, 1);
        }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            align-items: center;
        }

        .calendar-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            padding: 0.25rem 0.55rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.4);
            color: rgba(15, 23, 42, 0.9);
        }

        .dark .calendar-pill {
            background: rgba(15, 23, 42, 0.7);
            border-color: rgba(148, 163, 184, 0.2);
            color: rgba(226, 232, 240, 0.9);
        }

        .calendar-pill::before {
            content: "";
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: var(--pill, #94a3b8);
        }

        .calendar-hint {
            font-size: 0.8rem;
            color: rgba(100, 116, 139, 1);
        }

        .dark .calendar-hint {
            color: rgba(148, 163, 184, 0.85);
        }

        .calendar-shell .ec {
            --ec-event-bg-color: rgb(var(--primary-600));
            --ec-border-color: rgba(148, 163, 184, 0.25);
            --ec-button-border-color: rgba(15, 23, 42, 0.12);
            --ec-button-bg-color: rgba(255, 255, 255, 1.0);
            --ec-button-active-bg-color: rgba(226, 232, 240, 0.8);
            --ec-button-active-border-color: var(--ec-button-border-color);
        }

        .dark .calendar-shell .ec {
            --ec-event-bg-color: rgb(var(--primary-500));
            --ec-border-color: rgba(148, 163, 184, 0.18);
            --ec-button-border-color: rgba(226, 232, 240, 0.15);
            --ec-button-bg-color: rgba(15, 23, 42, 0.8);
            --ec-button-active-bg-color: rgba(30, 41, 59, 0.9);
            --ec-button-active-border-color: var(--ec-button-border-color);
        }

        .calendar-shell .ec .ec-toolbar {
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.75rem;
        }

        .calendar-shell .ec .ec-toolbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .calendar-shell .ec .ec-button {
            border-radius: 999px;
            padding: 0.35rem 0.85rem;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
        }

        .calendar-shell .ec .ec-button:hover {
            transform: translateY(-1px);
            transition: transform 120ms ease;
        }

        .calendar-shell .ec .ec-daygrid-day-frame,
        .calendar-shell .ec .ec-timegrid-slot {
            border-radius: 0.85rem;
        }

        .calendar-shell .ec .ec-day-today {
            background: rgba(14, 165, 233, 0.08);
        }

        .calendar-shell .ec .ec-event {
            border-radius: 0.6rem;
            padding: 0.1rem 0.35rem;
            box-shadow: 0 6px 12px rgba(15, 23, 42, 0.12);
        }

        .calendar-shell .ec .ec-event.ec-preview {
            --ec-event-bg-color: rgb(var(--primary-400));
            z-index: 30;
        }

        .calendar-shell .ec .ec-now-indicator {
            z-index: 40;
        }
    </style>

    <x-filament::section
        :header-actions="$this->getCachedHeaderActions()"
        :footer-actions="$this->getCachedFooterActions()"
    >
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <div class="calendar-shell">
            <div class="calendar-hero">
                <div class="calendar-title">
                    <h2>Ringkas, jelas, dan interaktif</h2>
                    <p>Gunakan klik tanggal untuk menambah jadwal, atau drag untuk mengubah.</p>
                </div>
                <div class="calendar-legend">
                    <span class="calendar-pill" style="--pill: #0ea5e9;">Tugas Proses</span>
                    <span class="calendar-pill" style="--pill: #16a34a;">Selesai</span>
                    <span class="calendar-pill" style="--pill: #ef4444;">Libur</span>
                    <span class="calendar-pill" style="--pill: #22c55e;">Event</span>
                    <span class="calendar-pill" style="--pill: #f59e0b;">Catatan</span>
                </div>
            </div>
            <div class="calendar-hint">
                Tip: klik event untuk detail, pilih rentang tanggal untuk membuat jadwal lebih cepat.
            </div>

            <div
                class="mt-4"
                wire:ignore
                x-ignore
                ax-load
                ax-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('calendar-widget', 'guava/calendar') }}"
                x-data="calendarWidget({
                    view: @js($this->getCalendarView()),
                    locale: @js($this->getLocale()),
                    firstDay: @js($this->getFirstDay()),
                    eventContent: @js($this->getEventContentJs()),
                    resourceLabelContent: @js($this->getResourceLabelContentJs()),
                    eventClickEnabled: @js($eventClickEnabled),
                    eventDragEnabled: @js($eventDragEnabled),
                    eventResizeEnabled: @js($eventResizeEnabled),
                    noEventsClickEnabled: @js($noEventsClickEnabled),
                    dateClickEnabled: @js($dateClickEnabled),
                    dateSelectEnabled: @js($dateSelectEnabled),
                    datesSetEnabled: @js($datesSetEnabled),
                    viewDidMountEnabled: @js($viewDidMountEnabled),
                    eventAllUpdatedEnabled: @js($eventAllUpdatedEnabled),
                    onEventResizeStart: @js($onEventResizeStart),
                    onEventResizeStop: @js($onEventResizeStop),
                    dayMaxEvents: @js($this->dayMaxEvents()),
                    moreLinkContent: @js($this->getMoreLinkContentJs()),
                    resources: @js($this->getResourcesJs()),
                    hasDateClickContextMenu: @js($hasDateClickContextMenu),
                    hasDateSelectContextMenu: @js($hasDateSelectContextMenu),
                    hasEventClickContextMenu: @js($hasEventClickContextMenu),
                    hasNoEventsClickContextMenu: @js($hasNoEventsClickContextMenu),
                    options: @js($this->getOptions()),
                    dayHeaderFormat: {{$dayHeaderFormatJs}},
                    slotLabelFormat: {{$slotLabelFormatJs}},
                })"
            >
                <div id="calendar"></div>
                <x-guava-calendar::context-menu/>
            </div>
        </div>
    </x-filament::section>
    <x-filament-actions::modals/>
</x-filament-widgets::widget>
