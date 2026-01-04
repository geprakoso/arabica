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
            margin-bottom: 20px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-uppercase {
            text-transform: uppercase;
        }
        .font-bold {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 6px 12px;
            vertical-align: top;
        }
        .section-header {
            background-color: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
        }
        .subsection-header {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .total-row {
            background-color: #f9fafb;
            font-weight: bold;
        }
        .grand-total {
            background-color: #f3f4f6;
            font-weight: bold;
            text-transform: uppercase;
        }
        .text-muted {
            color: #6b7280;
            font-style: italic;
        }
        .spacer {
            height: 16px;
        }
    </style>
</head>
<body>
    @php
        $company_name = data_get($data, 'company_name', '');
        $as_of_label = data_get($data, 'as_of_label', '-');
        $aset_lancar = data_get($data, 'aset_lancar', []);
        $aset_tidak_lancar = data_get($data, 'aset_tidak_lancar', []);
        $liabilitas_pendek = data_get($data, 'liabilitas_pendek', []);
        $liabilitas_panjang = data_get($data, 'liabilitas_panjang', []);
        // $ekuitas = data_get($data, 'ekuitas', []); // Removed as per request
        $totals = data_get($data, 'totals', []);
        $selisih = data_get($data, 'selisih', 0);

        $formatRupiah = function ($value): string {
            $value = (float) $value;
            $formatted = number_format(abs($value), 0, ',', '.');
            $label = 'Rp ' . $formatted;
            return $value < 0 ? '(' . $label . ')' : $label;
        };
    @endphp

    <div class="header">
        <div class="text-uppercase font-bold" style="font-size: 16px;">Laporan Neraca</div>
        <div class="text-muted" style="margin-bottom: 4px;">(Posisi Keuangan)</div>
        <div style="margin-bottom: 4px;">{{ $company_name }}</div>
        <div class="text-right">
            <div>Per {{ $as_of_label }}</div>
            <div class="text-muted" style="font-size: 10px;">(Dalam Rupiah)</div>
        </div>
    </div>

    <table>
        <tbody>
            <!-- ASET -->
            <tr class="section-header">
                <td>Aset</td>
                <td class="text-right"></td>
            </tr>
            
            <!-- Aset Lancar -->
            <tr class="subsection-header">
                <td>A. Aset Lancar</td>
                <td class="text-right"></td>
            </tr>
            @forelse ($aset_lancar as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td class="text-right">{{ $formatRupiah($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted">-</td>
                    <td class="text-right text-muted">0</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">{{ $formatRupiah($totals['aset_lancar']) }}</td>
            </tr>

            <tr><td colspan="2" class="spacer"></td></tr>

            <!-- Aset Tidak Lancar -->
            <tr class="subsection-header">
                <td>B. Aset Tidak Lancar</td>
                <td class="text-right"></td>
            </tr>
            @forelse ($aset_tidak_lancar as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td class="text-right">{{ $formatRupiah($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted">-</td>
                    <td class="text-right text-muted">0</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">{{ $formatRupiah($totals['aset_tidak_lancar']) }}</td>
            </tr>

            <!-- Total Aset -->
            <tr class="grand-total">
                <td>Total Aset Keseluruhan</td>
                <td class="text-right">{{ $formatRupiah($totals['aset']) }}</td>
            </tr>

            <tr><td colspan="2" class="spacer"></td></tr>

            <!-- LIABILITAS -->
            <tr class="section-header">
                <td>Liabilitas (Kewajiban)</td>
                <td class="text-right"></td>
            </tr>

            <!-- Liabilitas Jangka Pendek -->
            <tr class="subsection-header">
                <td>A. Liabilitas Jangka Pendek</td>
                <td class="text-right"></td>
            </tr>
            @forelse ($liabilitas_pendek as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td class="text-right">{{ $formatRupiah($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted">-</td>
                    <td class="text-right text-muted">0</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">{{ $formatRupiah($totals['liabilitas_pendek']) }}</td>
            </tr>

            <tr><td colspan="2" class="spacer"></td></tr>

            <!-- Liabilitas Jangka Panjang -->
            <tr class="subsection-header">
                <td>B. Liabilitas Jangka Panjang</td>
                <td class="text-right"></td>
            </tr>
            @forelse ($liabilitas_panjang as $row)
                <tr>
                    <td>{{ $row['nama'] }}</td>
                    <td class="text-right">{{ $formatRupiah($row['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td class="text-muted">-</td>
                    <td class="text-right text-muted">0</td>
                </tr>
            @endforelse
            <tr class="total-row">
                <td>Total</td>
                <td class="text-right">{{ $formatRupiah($totals['liabilitas_panjang']) }}</td>
            </tr>

            <!-- Total Liabilitas -->
            <tr class="grand-total">
                <td>Total Liabilitas Keseluruhan</td>
                <td class="text-right">{{ $formatRupiah($totals['liabilitas']) }}</td>
            </tr>

        </tbody>
    </table>

    <div class="text-right text-muted" style="margin-top: 20px; font-size: 10px;">
        Selisih: {{ $formatRupiah($selisih) }}
    </div>
</body>
</html>
