<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Neraca</title>
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
    $header_title = data_get($data, 'header_title', 'Laporan Neraca');
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

<div class="header">
    <div style="font-size: 16px; font-weight: 700; margin-bottom: 6px;">
        {{ $header_title }}
    </div>
    <div class="row">
        <div>{{ $company_name }}</div>
        <div>Per {{ $as_of_label }}</div>
    </div>
</div>

<table>
    <tbody>
    <tr class="section">
        <td>Aset</td>
        <td class="right"></td>
    </tr>
    
    <tr class="subtotal">
        <td class="pt">A. Aset Lancar</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($aset_lancar as $row)
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
    <tr class="subtotal">
        <td>Total Aset Lancar</td>
        <td class="right">{{ $formatRupiah(data_get($totals, 'aset_lancar', 0)) }}</td>
    </tr>

    <tr class="subtotal">
        <td class="pt">B. Aset Tidak Lancar</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($aset_tidak_lancar as $row)
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
    <tr class="subtotal">
        <td>Total Aset Tidak Lancar</td>
        <td class="right">{{ $formatRupiah(data_get($totals, 'aset_tidak_lancar', 0)) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Total Aset Keseluruhan</td>
        <td class="right pt">{{ $formatRupiah(data_get($totals, 'aset', 0)) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Liabilitas (Kewajiban)</td>
        <td class="right pt"></td>
    </tr>

    <tr class="subtotal">
        <td class="pt">A. Liabilitas Jangka Pendek</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($liabilitas_pendek as $row)
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
    <tr class="subtotal">
        <td>Total Liabilitas Jangka Pendek</td>
        <td class="right">{{ $formatRupiah(data_get($totals, 'liabilitas_pendek', 0)) }}</td>
    </tr>

    <tr class="subtotal">
        <td class="pt">B. Liabilitas Jangka Panjang</td>
        <td class="right pt"></td>
    </tr>
    @forelse ($liabilitas_panjang as $row)
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
    <tr class="subtotal">
        <td>Total Liabilitas Jangka Panjang</td>
        <td class="right">{{ $formatRupiah(data_get($totals, 'liabilitas_panjang', 0)) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Total Liabilitas Keseluruhan</td>
        <td class="right pt">{{ $formatRupiah(data_get($totals, 'liabilitas', 0)) }}</td>
    </tr>

    <tr class="section">
        <td class="pt">Selisih</td>
        <td class="right pt">{{ $formatRupiah($selisih) }}</td>
    </tr>
    </tbody>
</table>
</body>
</html>
