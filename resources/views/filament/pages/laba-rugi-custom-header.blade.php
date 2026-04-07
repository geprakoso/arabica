@php
    $heading = $this->getHeading();
    $subheading = $this->getSubheading();
    $breadcrumbs = filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [];
    $bulananUrl = \App\Filament\Resources\Akunting\LaporanLabaRugiResource::getUrl('index');
    $detailUrl = \App\Filament\Pages\LabaRugiCustom::getUrl();
    $currentUrl = url()->current();
@endphp

<header class="fi-header flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        @if ($breadcrumbs)
            <x-filament::breadcrumbs
                :breadcrumbs="$breadcrumbs"
                class="mb-2 hidden sm:block"
            />
        @endif

        <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
            {{ $heading }}
        </h1>

        @if ($subheading)
            <p class="fi-header-subheading mt-2 max-w-2xl text-lg text-gray-600 dark:text-gray-400">
                {{ $subheading }}
            </p>
        @endif

        <div class="mt-4">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$currentUrl === $bulananUrl"
                    :href="$bulananUrl"
                    tag="a"
                >
                    Bulanan
                </x-filament::tabs.item>
                <x-filament::tabs.item
                    :active="$currentUrl === $detailUrl"
                    :href="$detailUrl"
                    tag="a"
                >
                    Detail
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>
    </div>

    <div class="flex w-full flex-col gap-2 sm:w-auto sm:items-end">
        <div class="w-full sm:w-auto sm:max-w-sm sm:self-end">
            {{ $this->form }}
        </div>
    </div>
</header>
