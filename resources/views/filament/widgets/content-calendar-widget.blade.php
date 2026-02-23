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

<x-filament-widgets::widget class="fi-widgets-content-calendar-widget">
    {{-- Custom Styles matching Kalender Kerja --}}
    <style>
        .fi-widgets-content-calendar-widget .ec {
            --ec-event-bg-color: rgb(var(--primary-600));
            --ec-border-color: rgb(var(--gray-200));
            --ec-button-border-color: rgba(var(--gray-950), 0.1);
            --ec-button-bg-color: rgba(255, 255, 255, 1.0);
            --ec-button-active-bg-color: rgba(var(--gray-50), 1.0);
            --ec-button-active-border-color: var(--ec-button-border-color);
            --ec-today-bg-color: rgba(16, 88, 221, 0.14);
        }

        .fi-widgets-content-calendar-widget .ec .ec-event.ec-preview {
                --ec-event-bg-color: rgb(var(--primary-400));
                z-index: 30;
        }

        .fi-widgets-content-calendar-widget .ec .ec-now-indicator {
                z-index: 40;
        }

        .fi-widgets-content-calendar-widget .ec .ec-header {
                background-color: rgb(var(--primary-50));
                color: rgb(var(--primary-700));
                border-bottom: 1px solid var(--ec-border-color);
                border-radius: 10px 10px 0 0;
        }

        .fi-widgets-content-calendar-widget .ec .ec-body {
                background-color: rgb(var(--gray-20));
                border-radius: 0 0 10px 10px;
        }

        .fi-widgets-content-calendar-widget .ec .ec-today,
        .fi-widgets-content-calendar-widget .ec .ec-day.ec-today {
            background-color: var(--ec-today-bg-color) !important;
            border: 1px solid rgba(16, 88, 221, 0.14);
            border-radius: 10px;
        }

        .fi-widgets-content-calendar-widget .ec .ec-day.ec-today .ec-day-head time {
            color: rgb(2, 132, 199);
            font-weight: 700;
        }

        .dark .fi-widgets-content-calendar-widget .ec {
            --ec-event-bg-color: rgb(var(--primary-500));
            --ec-border-color: rgba(255, 255, 255, 0.10);
            --ec-button-border-color: rgba(var(--gray-600), 1.0);
            --ec-button-bg-color: rgba(255, 255, 255, 0.05);
            --ec-button-active-bg-color: rgba(255, 255, 255, 0.1);
            --ec-button-active-border-color: var(--ec-button-border-color);
            --ec-today-bg-color: rgba(var(--primary-600), 0.14);
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-event.ec-preview {
                --ec-event-bg-color: rgb(var(--primary-300));
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-header {
                background-color: rgba(var(--primary-500), 0.1);
                color: rgb(var(--primary-400));
                border-bottom: 1px solid var(--ec-border-color);
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-body {
                background-color: rgba(255, 255, 255, 0.02);
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-today,
        .dark .fi-widgets-content-calendar-widget .ec .ec-day.ec-today {
            background-color: var(--ec-today-bg-color) !important;
            border: 1px solid rgba(var(--primary-600), 0.14);
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-day.ec-today .ec-day-head time {
            color: rgb(186, 230, 253);
            font-weight: 700;
        }

        /* Rich event cards - Loomly style */
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 4px !important;
            padding: 0 !important;
            overflow: hidden;
            color: #111827 !important;
            cursor: pointer;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-1px);
        }

        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event .ec-event-time {
            display: none !important;
        }

        /* Dark mode rich cards */
        .dark .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event {
            background: rgb(31, 41, 55) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f3f4f6 !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .dark .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        /* Pillar left border accent */
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event.event-info {
            border-left: 3px solid #3b82f6 !important;
        }
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event.event-danger {
            border-left: 3px solid #ef4444 !important;
        }
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event.event-warning {
            border-left: 3px solid #f59e0b !important;
        }
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event.event-success {
            border-left: 3px solid #22c55e !important;
        }
        .fi-widgets-content-calendar-widget .ec .ec-event.cc-rich-event.event-gray {
            border-left: 3px solid #6b7280 !important;
        }
    </style>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div
            class="fi-section-header flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-white/10">
            {{-- Heading --}}
            <div class="flex items-center gap-x-4">
                @if ($icon = $this->getIcon())
                    <x-filament::icon
                        :icon="$icon"
                        class="h-10 w-10 text-primary-600 dark:text-primary-400"
                    />
                @endif

                <div class="grid gap-y-1">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $this->getHeading() }}
                    </h2>

                    @if ($description = $this->getDescription())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Filters & Actions Wrapper --}}
            <div class="flex items-center gap-x-3">
                {{-- Form Filters (Month/Year) --}}
                <div class="flex items-center gap-2">
                    <select wire:model.live="month"
                        class="block w-full rounded-lg border-none bg-gray-50 py-1.5 px-3 text-sm font-medium text-gray-950 ring-1 ring-gray-950/10 hover:bg-primary-50 hover:text-primary-600 hover:ring-primary-600 focus:ring-2 focus:ring-primary-600 dark:bg-gray-50 dark:text-primary-600"
                        style="min-width: 100px;">
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>

                    <select wire:model.live="year"
                        class="block w-full rounded-lg border-none bg-gray-50 py-1.5 px-3 text-sm font-medium text-gray-950 ring-1 ring-gray-950/10 hover:bg-primary-50 hover:text-primary-600 hover:ring-primary-600 focus:ring-2 focus:ring-primary-600 dark:bg-gray-50 dark:text-primary-600"
                        style="min-width: 80px;">
                        @php
                            $currentYear = \Carbon\Carbon::now()->year;
                            $years = range($currentYear - 3, $currentYear + 3);
                        @endphp
                        @foreach ($years as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Original Actions buttons --}}
                @if ($actions = $this->getCachedHeaderActions())
                    <div class="flex items-center gap-x-3">
                        @foreach ($actions as $action)
                            {{ $action }}
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="fi-section-content p-6">
            <script>
                window.renderContentCalendarEvent = function(arg) {
                    let ep = arg.event.extendedProps || {};
                    let title = arg.event.title || '';
                    let time = ep.time || '';
                    let status = ep.statusLabel || '';
                    let pillar = ep.pillarLabel || '';
                    let tipe = ep.tipeKonten || '';
                    let platforms = ep.platforms || [];

                    // Status colors
                    let statusColors = { published: '#16a34a', scheduled: '#2563eb', waiting: '#d97706', draft: '#6b7280' };
                    let statusColor = statusColors[ep.status] || '#6b7280';

                    // Pillar colors
                    let pillarColors = { edukasi: '#3b82f6', promo: '#ef4444', branding: '#f59e0b', engagement: '#22c55e', testimoni: '#6366f1' };
                    let pillarBg = pillarColors[ep.pillar] || '#6b7280';

                    // Platform icons
                    let platformIcons = {
                        instagram: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);color:#fff;font-size:10px;font-weight:700;">IG<\/span>',
                        tiktok: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#000;color:#fff;font-size:9px;font-weight:700;">TT<\/span>',
                        facebook: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#1877f2;color:#fff;font-size:10px;font-weight:700;">f<\/span>',
                        twitter: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#000;color:#fff;font-size:10px;font-weight:700;">𝕏<\/span>',
                        youtube: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#ff0000;color:#fff;font-size:10px;font-weight:700;">▶<\/span>',
                        linkedin: '<span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:50%;background:#0a66c2;color:#fff;font-size:10px;font-weight:700;">in<\/span>',
                    };

                    let platformHtml = platforms.map(p => platformIcons[p] || '').join(' ');
                    let pillarHtml = pillar ? `<span style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;color:#fff;background:${pillarBg};">${pillar}<\/span>` : '';
                    let tipeHtml = tipe ? `<span style="display:inline-block;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:500;color:#374151;background:#e5e7eb;">${tipe}<\/span>` : '';
                    
                    let isDarkMode = document.documentElement.classList.contains('dark');
                    let timeColor = isDarkMode ? '#9ca3af' : '#6b7280';
                    let titleColor = isDarkMode ? '#f3f4f6' : '#111827';
                    let html = `<div style="padding:4px 6px;line-height:1.3;cursor:pointer;color:${titleColor};">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:4px;">
                            <span style="font-weight:600;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;">${title}<\/span>
                            <span style="font-size:10px;color:${timeColor};white-space:nowrap;">${time}<\/span>
                        <\/div>
                        <div style="display:flex;align-items:center;gap:4px;margin-top:2px;">
                            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${statusColor};flex-shrink:0;"><\/span>
                            <span style="font-size:10px;color:${timeColor};">${status}<\/span>
                        <\/div>`;
                    
                    if (pillarHtml || tipeHtml) {
                        html += `<div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px;">${pillarHtml}${tipeHtml}<\/div>`;
                    }
                    if (platformHtml) {
                        html += `<div style="display:flex;gap:3px;margin-top:4px;">${platformHtml}<\/div>`;
                    }
                    html += `<\/div>`;

                    return { html: html };
                };
            </script>
            <div wire:ignore x-ignore ax-load
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
                    options: {
                        ...(@js($this->getOptions())),
                        eventContent: window.renderContentCalendarEvent
                    },
                    dayHeaderFormat: {{ $dayHeaderFormatJs }},
                    slotLabelFormat: {{ $slotLabelFormatJs }},
                })">
                <div id="calendar"></div>
                <x-guava-calendar::context-menu />
            </div>
        </div>
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
