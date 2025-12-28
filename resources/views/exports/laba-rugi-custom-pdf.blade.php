<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111827;
        }
        .header {
            margin-bottom: 12px;
        }
        .row {
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 6px 12px;
            vertical-align: top;
        }
        .section {
            background: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
        }
        .subtotal {
            background: #f9fafb;
            font-weight: 700;
        }
        .summary {
            background: #f3f4f6;
            font-weight: 700;
        }
        .right {
            text-align: right;
        }
        .pt {
            padding-top: 10px;
        }
        .muted {
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
@php
    $header_title = data_get($data, 'header_title', 'Laporan Laba Rugi');
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

<div class="header">
    <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">
        {{ $header_title }}
    </div>
    <div class="row">
        <div>{{ $company_name }}</div>
        <div>Periode: {{ $periode_label }}</div>
    </div>
</div>

<table>
    <tbody>
    <tr class="section">
        <td>Pendapatan</td>
        <td class="right"></td>
    </tr>
    <tr>
        <td>Total Penjualan</td>
        <td class="right">{{ $formatRupiah($total_penjualan) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Beban Pokok Penjualan</td>
        <td class="right pt"></td>
    </tr>
    <tr>
        <td>Harga Pokok Penjualan</td>
        <td class="right">{{ $formatRupiah($total_hpp) }}</td>
    </tr>
    <tr class="subtotal">
        <td class="pt">Laba Kotor</td>
        <td class="right pt">{{ $formatRupiah($laba_kotor) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Beban Usaha</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($beban_rows as $row)
        <tr>
            <td>{{ $row['nama'] }}</td>
            <td class="right">{{ $formatRupiah($row['total']) }}</td>
        </tr>
    @empty
        <tr>
            <td class="muted">-</td>
            <td class="right muted">{{ $formatRupiah(0) }}</td>
        </tr>
    @endforelse
    {{-- <tr class="subtotal">
        <td>Total Beban Usaha</td>
        <td class="right">{{ $formatRupiah($total_beban) }}</td>
    </tr> --}}
    <tr class="subtotal">
        <td class="pt">Laba Usaha</td>
        <td class="right pt">{{ $formatRupiah($laba_usaha) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Pendapatan Lain-lain</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($pendapatan_lain_rows as $row)
        <tr>
            <td>{{ $row['nama'] }}</td>
            <td class="right">{{ $formatRupiah($row['total']) }}</td>
        </tr>
    @empty
        <tr>
            <td class="muted">-</td>
            <td class="right muted">{{ $formatRupiah(0) }}</td>
        </tr>
    @endforelse
    {{-- <tr class="subtotal">
        <td>Total Pendapatan Lain-lain</td>
        <td class="right">{{ $formatRupiah($total_pendapatan_lain) }}</td>
    </tr> --}}

    <tr class="summary">
        <td class="pt">Laba Sebelum Pajak</td>
        <td class="right pt">{{ $formatRupiah($laba_sebelum_pajak) }}</td>
    </tr>
    <tr class="section">
        <td class="pt">Laba Bersih</td>
        <td class="right pt">{{ $formatRupiah($laba_bersih) }}</td>
    </tr>
    </tbody>
</table>
</body>
</html>
