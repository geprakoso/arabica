@php
    $data = $getState() ?? [];
    $company_name = data_get($data, 'company_name', '');
    $periode_label = data_get($data, 'periode_label', '-');
    $total_penjualan = data_get($data, 'total_penjualan', 0);
    $total_hpp = data_get($data, 'total_hpp', 0);
    $laba_kotor = data_get($data, 'laba_kotor', 0);
    $beban_rows = data_get($data, 'beban_rows', []);
    $total_beban = data_get($data, 'total_beban', 0);
    $laba_usaha = data_get($data, 'laba_usaha', 0);
    $pendapatan_lain_rows = data_get($data, 'pendapatan_lain_rows', []);
    $total_pendapatan_lain = data_get($data, 'total_pendapatan_lain', 0);
    $laba_sebelum_pajak = data_get($data, 'laba_sebelum_pajak', 0);
    $laba_bersih = data_get($data, 'laba_bersih', 0);

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
                <div class="text-sm dark:text-white">Periode: {{ $periode_label }}</div>
            </div>
        </div>

        {{-- 
           Fix: Added dark:bg-gray-900 and dark:border-white/10 to the container 
           to match Filament's card style.
        --}}
        <div class="w-full max-w-none overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
            <table class="w-full min-w-full text-sm text-gray-950 dark:text-white">
                <tbody>
                {{-- 
                   Fix: Changed bg-gray-100 to handle dark mode. 
                   dark:bg-white/5 is a standard Filament technique for subtle highlights in dark mode.
                --}}
                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Pendapatan</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Total Penjualan</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($total_penjualan) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Beban Pokok Penjualan</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Harga Pokok Penjualan</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($total_hpp) }}</td>
                </tr>
                {{-- 
                   Fix: bg-gray-50 is very bright. 
                   We keep it for light mode, but use dark:bg-white/5 for dark mode consistency.
                --}}
                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Laba Kotor</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($laba_kotor) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Beban Usaha</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($beban_rows as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse

                <tr class="bg-gray-50 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Laba Usaha</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($laba_usaha) }}</td>
                </tr>

                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Pendapatan Lain-lain</td>
                    <td class="px-4 py-2 text-right"></td>
                </tr>
                @forelse ($pendapatan_lain_rows as $row)
                    <tr>
                        <td class="px-4 py-2">{{ $row['nama'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $formatRupiah($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-2 text-gray-500 italic dark:text-gray-400">-</td>
                        <td class="px-4 py-2 text-right text-gray-500 dark:text-gray-400">{{ $formatRupiah(0) }}</td>
                    </tr>
                @endforelse

                <tr class="bg-gray-100 font-semibold dark:bg-white/5">
                    <td class="px-4 py-2">Laba Sebelum Pajak</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($laba_sebelum_pajak) }}</td>
                </tr>
                <tr class="bg-gray-100 font-semibold uppercase dark:bg-white/5">
                    <td class="px-4 py-2">Laba Bersih</td>
                    <td class="px-4 py-2 text-right">{{ $formatRupiah($laba_bersih) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament::section>