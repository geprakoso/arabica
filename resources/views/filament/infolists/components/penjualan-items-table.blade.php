@php
    $items = $getState() ?? [];
@endphp

<div class="overflow-x-auto max-w-full text-sm" style="-webkit-overflow-scrolling: touch;">
    <table class="min-w-[66rem] w-full divide-y divide-gray-200 dark:divide-white/10">
        <thead class="bg-gray-50/80 dark:bg-white/5">
            <tr class="text-left text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                <th class="w-[22rem] px-3 py-2">Produk</th>
                <th class="w-[10rem] px-3 py-2">Batch (No. PO)</th>
                <th class="w-[8rem] px-3 py-2">Tgl Batch</th>
                <th class="w-[8rem] px-3 py-2 text-center">Kondisi</th>
                <th class="w-[5rem] px-3 py-2 text-center">Qty</th>
                <th class="w-[10rem] px-3 py-2 text-right">HPP</th>
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

                    $batchPo = data_get($item, 'pembelianItem.pembelian.no_po');
                    $batchTanggal = data_get($item, 'pembelianItem.pembelian.tanggal');

                    $batchTanggalLabel = '-';
                    if ($batchTanggal) {
                        try {
                            $batchTanggalLabel = \Illuminate\Support\Carbon::parse($batchTanggal)->format('d M Y');
                        } catch (\Throwable $e) {
                            $batchTanggalLabel = (string) $batchTanggal;
                        }
                    }
                @endphp

                <tr class="bg-white dark:bg-transparent hover:bg-gray-50/70 dark:hover:bg-white/5">
                    <td class="px-3 py-3">
                        <div class="max-w-[22rem] truncate font-medium text-gray-900 dark:text-gray-100">
                            {{ data_get($item, 'produk.nama_produk') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200 whitespace-nowrap">
                        {{ $batchPo ? "#{$batchPo}" : '-' }}
                    </td>
                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200 whitespace-nowrap">
                        {{ $batchTanggalLabel }}
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
                    <td colspan="7" class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        Belum ada item penjualan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

