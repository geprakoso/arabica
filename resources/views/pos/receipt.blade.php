@php
    $items = $penjualan->items ?? collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $penjualan->no_nota }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; padding: 16px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { font-size: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 0; font-size: 13px; }
        th { text-align: left; border-bottom: 1px solid #ddd; }
        tfoot td { font-weight: bold; }
        .right { text-align: right; }
        .actions { margin-top: 16px; }
        @media print {
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <h1>Struk Penjualan</h1>
    <div class="meta">
        <div>No Nota: {{ $penjualan->no_nota }}</div>
        <div>Tanggal: {{ $penjualan->tanggal_penjualan }}</div>
        <div>Kasir: {{ $penjualan->karyawan->nama_karyawan ?? '-' }}</div>
        <div>Metode Bayar: {{ $penjualan->metode_bayar ?? '-' }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th class="right">Qty</th>
            <th class="right">Harga</th>
            <th class="right">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->produk->nama_produk ?? 'Item' }}</td>
                <td class="right">{{ $item->qty }}</td>
                <td class="right">{{ number_format((float) ($item->harga_jual ?? 0), 0, ',', '.') }}</td>
                <td class="right">{{ number_format((float) ($item->qty * ($item->harga_jual ?? 0)), 0, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3" class="right">Total</td>
            <td class="right">{{ number_format((float) $penjualan->total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="right">Diskon</td>
            <td class="right">{{ number_format((float) $penjualan->diskon_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="right">Grand Total</td>
            <td class="right">{{ number_format((float) $penjualan->grand_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="right">Tunai Diterima</td>
            <td class="right">{{ number_format((float) ($penjualan->tunai_diterima ?? 0), 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="3" class="right">Kembalian</td>
            <td class="right">{{ number_format((float) ($penjualan->kembalian ?? 0), 0, ',', '.') }}</td>
        </tr>
        </tfoot>
    </table>

    <div class="actions">
        <button onclick="window.print()">Print</button>
    </div>
</body>
</html>
