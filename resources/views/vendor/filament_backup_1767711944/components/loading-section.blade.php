@php
    /*
     * This view overrides Filament's built-in widget placeholder.
     * It must accept the same variables Filament passes:
     * - $columnSpan (int|string|array|null)
     * - $columnStart (int|string|array|null)
     * - $height (string|null)
     */

    if ((! isset($columnSpan)) || (! is_array($columnSpan))) {
        $columnSpan = [
            'default' => $columnSpan ?? null,
        ];
    }

    if ((! isset($columnStart)) || (! is_array($columnStart))) {
        $columnStart = [
            'default' => $columnStart ?? null,
        ];
    }

    $height ??= '10rem';
@endphp

<x-filament::grid.column
    :default="$columnSpan['default'] ?? 1"
    :sm="$columnSpan['sm'] ?? null"
    :md="$columnSpan['md'] ?? null"
    :lg="$columnSpan['lg'] ?? null"
    :xl="$columnSpan['xl'] ?? null"
    :twoXl="$columnSpan['2xl'] ?? null"
    :defaultStart="$columnStart['default'] ?? null"
    :smStart="$columnStart['sm'] ?? null"
    :mdStart="$columnStart['md'] ?? null"
    :lgStart="$columnStart['lg'] ?? null"
    :xlStart="$columnStart['xl'] ?? null"
    :twoXlStart="$columnStart['2xl'] ?? null"
    class="fi-loading-section"
>
    <x-filament::section class="relative overflow-hidden" style="height: {{ $height }}">
        <div class="flex h-full w-full flex-col gap-y-4 animate-pulse">
            {{-- Header: Circular Avatar/Icon + Title --}}
            <div class="flex items-center gap-x-3">
                <div class="h-10 w-10 shrink-0 rounded-full bg-gray-200 dark:bg-gray-800"></div>
                <div class="space-y-2">
                    <div class="h-4 w-32 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                    <div class="h-3 w-20 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                </div>
            </div>

            {{-- Body: Random width lines for realism --}}
            <div class="flex-1 space-y-3 py-2">
                <div class="h-3 w-full rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                <div class="h-3 w-11/12 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                <div class="h-3 w-4/5 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
            </div>

            {{-- Footer: Action Bar placeholders --}}
            <div class="flex gap-x-3 pt-2">
                <div class="h-8 w-20 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
                <div class="h-8 w-20 rounded-lg bg-gray-200 dark:bg-gray-800"></div>
            </div>
        </div>
    </x-filament::section>
</x-filament::grid.column>
