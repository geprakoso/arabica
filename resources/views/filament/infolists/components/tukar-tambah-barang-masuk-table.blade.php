@php
    $rows = $rows ?? (isset($getState) ? ($getState() ?? []) : []);

    if ($rows instanceof \Illuminate\Support\Collection) {
        $rows = $rows->all();
    }

    if (! is_iterable($rows)) {
        $rows = [];
    }
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <table class="lr-table min-w-full w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-[20rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Produk</th>
                <th class="w-[8rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Kondisi</th>
                <th class="w-[5rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">HPP (Beli)</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Subtotal</th>
                <th class="w-[8rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Aksi</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($rows as $item)
                @php
                    $condition = data_get($item, 'kondisi');
                    $badgeMap = [
                        'baru' => 'success',
                        'new' => 'success',
                        'bekas' => 'warning',
                        'refurbished' => 'warning',
                        'rusak' => 'danger',
                    ];
                    $badgeColor = $badgeMap[strtolower($condition ?? '')] ?? 'gray';
                    $conditionLabel = $condition ? ucfirst((string) $condition) : '-';

                    $hpp = (float) (data_get($item, 'hpp') ?? 0);
                    $qty = (int) (data_get($item, 'qty') ?? 0);
                    $subtotal = $qty * $hpp;

                    $pembelianItemId = data_get($item, 'id');
                    $produkId = data_get($item, 'id_produk');
                @endphp

                <tr class="lr-row border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100 dark:hover:bg-gray-900/50 dark:hover:[&>td]:bg-gray-900/50">
                    <td class="px-4 py-3">
                        <div class="max-w-[20rem] truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ data_get($item, 'produk.nama_produk') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <x-filament::badge color="{{ $badgeColor }}" size="md" class="font-normal px-3 py-1 whitespace-nowrap">
                            {{ $conditionLabel }}
                        </x-filament::badge>
                    </td>
                    <td class="px-4 py-3 text-center font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ number_format($qty, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($hpp, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                    {{-- <td class="px-4 py-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-2">
                            @if($produkId)
                                <a href="{{ route('filament.admin.resources.master-data.produks.view', ['record' => $produkId]) }}" 
                                   target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-warning-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition hover:scale-110"
                                   title="Lihat Produk">
                                    <x-heroicon-m-eye class="w-4 h-4" />
                                </a>
                                <a href="{{ route('filament.admin.resources.master-data.produks.edit', ['record' => $produkId]) }}" 
                                   target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-primary-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition hover:scale-110"
                                   title="Edit Produk">
                                    <x-heroicon-m-pencil-square class="w-4 h-4" />
                                </a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </div>
                    </td> --}}
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada item pembelian.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
