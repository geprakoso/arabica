@php
    $rows = $rows ?? (isset($getState) ? $getState() : []);
    $totalHpp = collect($rows)->sum(fn ($row) => (float) ($row->hpp ?? 0) * (int) ($row->qty ?? 0));

    if ($rows instanceof \Illuminate\Support\Collection) {
        $rows = $rows->all();
    }
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="w-full table-fixed divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="w-56 px-4 py-3 text-left font-semibold text-gray-700">Nama Produk</th>
                <th class="w-24 px-4 py-3 text-right font-semibold text-gray-700">Qty</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Supplier</th>
                <th class="w-32 px-4 py-3 text-left font-semibold text-gray-700">HPP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                @php
                    $pembelian = $row->pembelian;
                    $url = $pembelian
                        ? \App\Filament\Resources\PembelianResource::getUrl('view', ['record' => $pembelian])
                        : null;
                @endphp
                <tr
                    class="{{ $url ? 'cursor-pointer hover:bg-gray-50' : '' }}"
                    @if ($url)
                        role="link"
                        tabindex="0"
                        onclick="window.open('{{ $url }}', '_blank', 'noopener')"
                        onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.open('{{ $url }}', '_blank', 'noopener'); }"
                    @endif
                >
                    <td class="px-4 py-3 text-gray-800">
                        {{ $row->produk?->nama_produk ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-gray-800">
                        {{ (int) ($row->qty ?? 0) }}
                    </td>
                    <td class="px-4 py-3 text-gray-800">
                        {{ $pembelian?->supplier?->nama_supplier ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                        Rp {{ number_format((float) ($row->hpp ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-gray-500" colspan="4">
                        Tidak ada data pembelian untuk bulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm">
        <span class="text-gray-600">Total Pembelian</span>
        <span class="font-semibold text-gray-900">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
    </div>
</div>
