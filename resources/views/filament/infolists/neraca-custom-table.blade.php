@php
    $data = $getState() ?? [];
    $company_name = data_get($data, 'company_name', '');
    $as_of_label = data_get($data, 'as_of_label', '-');
    $aset_lancar = data_get($data, 'aset_lancar', []);
    $aset_tidak_lancar = data_get($data, 'aset_tidak_lancar', []);
    $liabilitas_pendek = data_get($data, 'liabilitas_pendek', []);
    $liabilitas_panjang = data_get($data, 'liabilitas_panjang', []);
    $totals = data_get($data, 'totals', []);
    $selisih = data_get($data, 'selisih', 0);

    $formatRupiah = function ($value): string {
        $value = (float) $value;
        $formatted = number_format(abs($value), 0, ',', '.');
        $label = 'Rp ' . $formatted;

        return $value < 0 ? '- ' . $label : $label;
    };
@endphp

<x-filament::section heading="Ringkasan">
    <x-slot name="headerEnd">
        <div class="ms-auto flex items-center">
            <div class="w-full max-w-sm">
                {{ $this->getForm('filtersForm') }}
            </div>
        </div>
    </x-slot>

    <div class="w-full max-w-none space-y-4">
        <div class="flex w-full flex-col gap-1 md:flex-row md:items-start md:justify-between">
            <div class="text-sm dark:text-white">{{ $company_name }}</div>
            <div class="text-right">
                <div class="text-sm dark:text-white">Per {{ $as_of_label }}</div>
            </div>
        </div>

        <div class="w-full max-w-none overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <table class="w-full min-w-full text-sm text-gray-950 dark:text-white">
                <tbody>
                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Aset</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>

                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
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
                        <td class="px-4 py-2 italic text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Total Aset Lancar</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset_lancar']) }}</td>
                </tr>

                <tr><td colspan="2" class="h-4"></td></tr>

                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
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
                        <td class="px-4 py-2 italic text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Total Aset Tidak Lancar</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset_tidak_lancar']) }}</td>
                </tr>

                <tr><td colspan="2" class="h-4"></td></tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Total Aset Keseluruhan</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['aset']) }}</td>
                </tr>

                <tr><td colspan="2" class="h-4"></td></tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Liabilitas (Kewajiban)</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>

                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
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
                        <td class="px-4 py-2 italic text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Total Liabilitas Jangka Pendek</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas_pendek']) }}</td>
                </tr>

                <tr><td colspan="2" class="h-4"></td></tr>

                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
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
                        <td class="px-4 py-2 italic text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse
                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Total Liabilitas Jangka Panjang</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas_panjang']) }}</td>
                </tr>

                <tr><td colspan="2" class="h-4"></td></tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Total Liabilitas Keseluruhan</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($totals['liabilitas']) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Selisih</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($selisih) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament::section>
