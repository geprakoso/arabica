@php
    $rows = $rows ?? (isset($getState) ? $getState() : []);
    $resolvedRows = $rows;

    if ($resolvedRows instanceof \Illuminate\Pagination\Paginator || $resolvedRows instanceof \Illuminate\Pagination\LengthAwarePaginator) {
        $resolvedRows = $resolvedRows->items();
    }

    if ($resolvedRows instanceof \Illuminate\Support\Collection) {
        $resolvedRows = $resolvedRows->all();
    }

    $totalPenjualan = $totalPenjualan ?? collect($resolvedRows)->sum(function ($row): float {
        $produkTotal = (float) $row->items->sum(function ($item): float {
            $qty = (int) ($item->qty ?? 0);
            $harga = (float) ($item->harga_jual ?? 0);

            return $harga * $qty;
        });
        $jasaTotal = (float) $row->jasaItems->sum(function ($service): float {
            $qty = max(1, (int) ($service->qty ?? 1));
            $harga = (float) ($service->harga ?? 0);

            return $harga * $qty;
        });

        return $produkTotal + $jasaTotal;
    });
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <table class="lr-table w-full table-fixed divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-40 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">No. Nota</th>
                <th class="w-32 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Tanggal</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Member</th>
                <th class="w-40 px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Grand Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($rows as $row)
                @php
                    $url = \App\Filament\Resources\PenjualanResource::getUrl('view', ['record' => $row]);
                    $produkTotal = (float) $row->items->sum(function ($item): float {
                        $qty = (int) ($item->qty ?? 0);
                        $harga = (float) ($item->harga_jual ?? 0);

                        return $harga * $qty;
                    });
                    $jasaTotal = (float) $row->jasaItems->sum(function ($service): float {
                        $qty = max(1, (int) ($service->qty ?? 1));
                        $harga = (float) ($service->harga ?? 0);

                        return $harga * $qty;
                    });
                    $grandTotal = $produkTotal + $jasaTotal;
                @endphp
                <tr
                    class="lr-row group cursor-pointer border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100"
                    role="link"
                    tabindex="0"
                    onclick="window.location.href='{{ $url }}'"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $url }}'; }"
                >
                    <td class="px-4 py-3 whitespace-nowrap text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ $row->no_nota ?? '-' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ optional($row->tanggal_penjualan)->format('d M Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ $row->member?->nama_member ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-gray-900 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400" colspan="4">
                        Tidak ada data penjualan untuk bulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">
        <span class="text-gray-600 dark:text-gray-200">Total Penjualan</span>
        <span class="font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</span>
    </div>
    @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator && $rows->hasPages())
        <div class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
            {{ $rows->onEachSide(1)->links() }}
        </div>
    @endif
</div>
