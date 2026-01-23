@php
    $items = $getState() ?? [];

    if ($items instanceof \Illuminate\Support\Collection) {
        $items = $items->all();
    }

    $totalPembelian = collect($items)->sum(fn ($item) => (float) (data_get($item, 'hpp') ?? 0)
        * (int) (data_get($item, 'qty') ?? 0));
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <table class="min-w-[66rem] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-[18rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Produk</th>
                <th class="w-[10rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">SN</th>
                <th class="w-[8rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Garansi</th>
                <th class="w-[8rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Kondisi</th>
                <th class="w-[5rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Harga Beli</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Harga Jual</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($items as $item)
                @php
                    $condition = data_get($item, 'kondisi');
                    $badgeMap = [
                        'baru' => 'success',
                        'new' => 'success',
                        'bekas' => 'warning',
                        'refurbished' => 'warning',
                        'rusak' => 'danger',
                    ];
                    $badgeColor = $badgeMap[strtolower($condition ?? '')] ?? 'primary';
                    $conditionLabel = $condition ? strtoupper((string) $condition) : '-';

                    $hpp = (float) (data_get($item, 'hpp') ?? 0);
                    $hargaJual = (float) (data_get($item, 'harga_jual') ?? 0);

                    // Parse serials data
                    $serials = data_get($item, 'serials');
                    $serialList = is_array($serials) ? collect($serials)->pluck('sn')->filter()->values() : collect();
                    $snLabel = $serialList->isNotEmpty() ? $serialList->implode(', ') : '-';

                    $garansiList = is_array($serials) ? collect($serials)->pluck('garansi')->filter()->values() : collect();
                    $garansiLabel = $garansiList->isNotEmpty() ? $garansiList->implode(', ') : '-';
                @endphp

                <tr class="border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100 dark:hover:bg-gray-900/50 dark:hover:[&>td]:bg-gray-900/50">
                    <td class="px-4 py-3">
                        <div class="max-w-[18rem] truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ data_get($item, 'produk.nama_produk') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ $snLabel }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ $garansiLabel }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <x-filament::badge color="{{ $badgeColor }}" size="md" class="font-normal uppercase px-3 py-1 whitespace-nowrap">
                            {{ $conditionLabel }}
                        </x-filament::badge>
                    </td>
                    <td class="px-4 py-3 text-center font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ number_format((int) (data_get($item, 'qty') ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($hpp, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($hargaJual, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada item barang.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
        <span>Total Pembelian</span>
        <span class="text-lg font-semibold text-success-600 dark:text-success-400">Rp {{ number_format($totalPembelian, 0, ',', '.') }}</span>
    </div>
</div>
