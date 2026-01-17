@php
    $rows = $rows ?? (isset($getState) ? $getState() : []);
    $rows = $rows ?: [];

    if ($rows instanceof \Illuminate\Support\Collection) {
        $rows = $rows->all();
    }

    $totalJasa = collect($rows)->sum(fn ($item) => (int) (data_get($item, 'qty') ?? 0)
        * (float) (data_get($item, 'harga') ?? 0));
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <table class="lr-table min-w-[48rem] w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-[20rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Jasa</th>
                <th class="w-[6rem] px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Tarif</th>
                <th class="w-[10rem] px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Subtotal</th>
                <th class="w-[16rem] px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Catatan</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($rows as $item)
                @php
                    $qty = (int) (data_get($item, 'qty') ?? 0);
                    $harga = (float) (data_get($item, 'harga') ?? 0);
                    $subtotal = $qty * $harga;
                @endphp

                <tr class="lr-row border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100 dark:hover:bg-gray-900/50 dark:hover:[&>td]:bg-gray-900/50">
                    <td class="px-4 py-3">
                        <div class="max-w-[20rem] truncate font-medium text-gray-800 dark:text-gray-100">
                            {{ data_get($item, 'jasa.nama_jasa') ?? '-' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-medium text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        {{ number_format($qty, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($harga, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 whitespace-nowrap">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100">
                        {{ data_get($item, 'catatan') ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                        Belum ada jasa pembelian.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
        <span>Total Jasa</span>
        <span class="text-lg font-semibold text-success-600 dark:text-success-400">Rp {{ number_format($totalJasa, 0, ',', '.') }}</span>
    </div>
</div>
