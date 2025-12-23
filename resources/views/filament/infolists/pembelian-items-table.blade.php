@php
    $rows = $rows ?? (isset($getState) ? $getState() : []);
    $resolvedRows = $rows;

    if ($resolvedRows instanceof \Illuminate\Pagination\Paginator || $resolvedRows instanceof \Illuminate\Pagination\LengthAwarePaginator) {
        $resolvedRows = $resolvedRows->items();
    }

    if ($resolvedRows instanceof \Illuminate\Support\Collection) {
        $resolvedRows = $resolvedRows->all();
    }

    $totalHpp = $totalHpp ?? collect($resolvedRows)->sum(fn ($row) => (float) ($row->hpp ?? 0) * (int) ($row->qty ?? 0));
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <table class="lr-table w-full table-fixed divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="w-56 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Nama Produk</th>
                <th class="w-24 px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Supplier</th>
                <th class="w-32 px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">HPP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($rows as $row)
                @php
                    $pembelian = $row->pembelian;
                    $url = $pembelian
                        ? \App\Filament\Resources\PembelianResource::getUrl('view', ['record' => $pembelian])
                        : null;
                @endphp
                <tr
                    class="{{ $url ? 'lr-row group cursor-pointer border-b border-gray-200 transition bg-white dark:border-gray-700 dark:bg-transparent hover:bg-gray-100 hover:[&>td]:bg-gray-100' : 'lr-row border-b border-gray-200 dark:border-gray-800' }}"
                    @if ($url)
                        role="link"
                        tabindex="0"
                        onclick="window.open('{{ $url }}', '_blank', 'noopener')"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.open('{{ $url }}', '_blank', 'noopener'); }"
                    @endif
                >
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ $row->produk?->nama_produk ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ (int) ($row->qty ?? 0) }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        {{ $pembelian?->supplier?->nama_supplier ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 dark:group-hover:!bg-gray-800/70">
                        Rp {{ number_format((float) ($row->hpp ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400" colspan="4">
                        Tidak ada data pembelian untuk bulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-800">
        <span class="text-gray-600 dark:text-gray-200">Total Pembelian</span>
        <span class="font-semibold text-gray-900 dark:text-gray-50">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
    </div>
    @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator && $rows->hasPages())
        <div class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
            {{ $rows->onEachSide(1)->links() }}
        </div>
    @endif
</div>
