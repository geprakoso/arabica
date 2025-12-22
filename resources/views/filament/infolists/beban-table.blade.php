@php
    $rows = $rows ?? (isset($getState) ? $getState() : []);
    $totalNominal = collect($rows)->sum(fn ($row) => (float) ($row->nominal_transaksi ?? 0));

    if ($rows instanceof \Illuminate\Support\Collection) {
        $rows = $rows->all();
    }
@endphp

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="w-full table-fixed divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="w-32 px-4 py-3 text-left font-semibold text-gray-700">Tanggal</th>
                <th class="w-48 px-4 py-3 text-left font-semibold text-gray-700">Jenis Akun</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-700">Keterangan</th>
                <th class="w-32 px-4 py-3 text-right font-semibold text-gray-700">Nominal</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                @php
                    $url = \App\Filament\Resources\Akunting\InputTransaksiTokoResource::getUrl('view', ['record' => $row]);
                @endphp
                <tr
                    class="cursor-pointer hover:bg-gray-50"
                    role="link"
                    tabindex="0"
                    onclick="window.open('{{ $url }}', '_blank', 'noopener')"
                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.open('{{ $url }}', '_blank', 'noopener'); }"
                >
                    <td class="px-4 py-3 whitespace-nowrap text-gray-800">
                        {{ optional($row->tanggal_transaksi)->format('d M Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800">
                        {{ $row->jenisAkun?->nama_jenis_akun ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-gray-800">
                        {{ $row->keterangan_transaksi ?: '-' }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold text-gray-900">
                        Rp {{ number_format((float) ($row->nominal_transaksi ?? 0), 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-3 text-center text-gray-500" colspan="4">
                        Tidak ada data beban untuk bulan ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm">
        <span class="text-gray-600">Total Beban</span>
        <span class="font-semibold text-gray-900">Rp {{ number_format($totalNominal, 0, ',', '.') }}</span>
    </div>
</div>
