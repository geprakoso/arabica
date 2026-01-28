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

<x-filament-widgets::widget class="fi-widgets-jadwal-kalender-widget">
    {{-- Custom Styles for this widget --}}
    <style>
        .ec {
            --ec-event-bg-color: rgb(var(--primary-600));
            --ec-border-color: rgb(var(--gray-200));
            --ec-button-border-color: rgba(var(--gray-950), 0.1);
            --ec-button-bg-color: rgba(255, 255, 255, 1.0);
            --ec-button-active-bg-color: rgba(var(--gray-50), 1.0);
            --ec-button-active-border-color: var(--ec-button-border-color);

            & .ec-event.ec-preview {
                --ec-event-bg-color: rgb(var(--primary-400));
                z-index: 30;
            }

            & .ec-now-indicator {
                z-index: 40;
            }
        }

        .dark .ec {
            --ec-event-bg-color: rgb(var(--primary-500));
            --ec-border-color: rgba(255, 255, 255, 0.10);
            --ec-button-border-color: rgba(var(--gray-600), 1.0);
            --ec-button-bg-color: rgba(255, 255, 255, 0.05);
            --ec-button-active-bg-color: rgba(255, 255, 255, 0.1);
            --ec-button-active-border-color: var(--ec-button-border-color);

            & .ec-event.ec-preview {
                --ec-event-bg-color: rgb(var(--primary-300));
            }
        }
    </style>

    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div
            class="fi-section-header flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-white/10">
            {{-- Heading --}}
            <div class="flex items-center gap-x-3">
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $this->getHeading() }}
                </h2>
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
                    options: @js($this->getOptions()),
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
