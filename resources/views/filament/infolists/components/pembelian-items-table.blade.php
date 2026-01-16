@php
    $items = $getState() ?? [];

    if ($items instanceof \Illuminate\Support\Collection) {
        $items = $items->all();
    }

    $totalPembelian = collect($items)->sum(fn ($item) => (float) (data_get($item, 'hpp') ?? 0)
        * (int) (data_get($item, 'qty') ?? 0));
@endphp

<div class="overflow-x-auto max-w-full text-sm" style="-webkit-overflow-scrolling: touch;">
    <table class="min-w-[50rem] w-full divide-y divide-gray-200 dark:divide-white/10">
        <thead class="bg-gray-50/80 dark:bg-white/5">
            <tr class="text-left text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                <th class="w-[22rem] px-3 py-2">Produk</th>
                <th class="w-[8rem] px-3 py-2 text-center">Kondisi</th>
                <th class="w-[5rem] px-3 py-2 text-center">Qty</th>
                <th class="w-[10rem] px-3 py-2 text-right">Harga Beli</th>
                <th class="w-[10rem] px-3 py-2 text-right">Harga Jual</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-white/10">
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
                @endphp

                <tr class="bg-white dark:bg-transparent hover:bg-gray-50/70 dark:hover:bg-white/5">
                    <td class="px-3 py-3">
                        <div class="max-w-[22rem] truncate font-medium text-gray-900 dark:text-gray-100">
                            {{ data_get($item, 'produk.nama_produk') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-3 py-3 text-center">
                        <x-filament::badge color="{{ $badgeColor }}" size="md" class="font-normal uppercase px-3 py-1 whitespace-nowrap">
                            {{ $conditionLabel }}
                        </x-filament::badge>
                    </td>
                    <td class="px-3 py-3 text-center font-medium text-gray-900 dark:text-gray-100 whitespace-nowrap">
                        {{ number_format((int) (data_get($item, 'qty') ?? 0), 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right text-gray-700 dark:text-gray-200 whitespace-nowrap">
                        Rp {{ number_format($hpp, 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-3 text-right font-medium text-primary-600 dark:text-primary-400 whitespace-nowrap">
                        Rp {{ number_format($hargaJual, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
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
