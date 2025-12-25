@php
    $formatRupiah = function ($value): string {
        $value = (float) $value;
        $formatted = number_format(abs($value), 0, ',', '.');

        return $value < 0 ? '(' . $formatted . ')' : $formatted;
    };
@endphp

<div class="space-y-4">
    <div class="text-center">
        <div class="text-lg font-semibold uppercase">Laporan Neraca</div>
        <div class="text-sm text-gray-500">(Posisi Keuangan)</div>
        <div class="text-sm">{{ $company_name }}</div>
        <div class="text-sm">Per {{ $as_of_label }}</div>
        <div class="text-xs text-gray-500">(Dalam Rupiah)</div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <tbody>
                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Aset</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">A. Aset Lancar</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($aset_lancar as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic">-</td>
                        <td class="px-4 py-2 text-right text-gray-500">0</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">Total Aset Lancar</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset_lancar']) }}</td>
                </tr>

                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">B. Aset Tidak Lancar</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($aset_tidak_lancar as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic">-</td>
                        <td class="px-4 py-2 text-right text-gray-500">0</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">Total Aset Tidak Lancar</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset_tidak_lancar']) }}</td>
                </tr>
                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Total Aset</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset']) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Liabilitas (Kewajiban)</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">A. Liabilitas Jangka Pendek</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($liabilitas_pendek as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic">-</td>
                        <td class="px-4 py-2 text-right text-gray-500">0</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">Total Liabilitas Jangka Pendek</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas_pendek']) }}</td>
                </tr>

                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">B. Liabilitas Jangka Panjang</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($liabilitas_panjang as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic">-</td>
                        <td class="px-4 py-2 text-right text-gray-500">0</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">Total Liabilitas Jangka Panjang</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas_panjang']) }}</td>
                </tr>
                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Total Liabilitas</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas']) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Ekuitas</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($ekuitas as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic">-</td>
                        <td class="px-4 py-2 text-right text-gray-500">0</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold">
                    <td class="px-4 py-2">Total Ekuitas</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['ekuitas']) }}</td>
                </tr>
                <tr class="bg-gray-100 font-semibold uppercase">
                    <td class="px-4 py-2">Total Kewajiban + Ekuitas</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas_ekuitas']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="text-right text-xs text-gray-500">
        Selisih: {{ $formatRupiah($selisih) }}
    </div>
</div>
