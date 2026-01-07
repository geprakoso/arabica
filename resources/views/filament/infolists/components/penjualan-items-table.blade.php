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
    <table class="lr-table min-w-[66rem] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-[22rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Produk</th>
                <th class="w-[10rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Batch (No. PO)</th>
                <th class="w-[8rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Tgl Batch</th>
                <th class="w-[8rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Kondisi</th>
                <th class="w-[5rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">HPP</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Harga Jual</th>
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

                <tr class="lr-row border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100 dark:hover:bg-gray-900/50 dark:hover:[&>td]:bg-gray-900/50">
                    <td class="px-4 py-3">
                        <div class="max-w-[22rem] truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ data_get($item, 'produk.nama_produk') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ $batchPo ? "#{$batchPo}" : '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ $batchTanggalLabel }}
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
                        Belum ada item penjualan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
