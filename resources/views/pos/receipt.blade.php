@php
    $items = $penjualan->items ?? collect();
    $services = $penjualan->jasaItems ?? collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $penjualan->no_nota }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reset & Base */
        * { box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
            color: #1f2937;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Layout Container */
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            position: relative;
        }

        /* Decorative Top Bar */
        .top-bar {
            height: 12px;
            background: linear-gradient(90deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        }

        /* Header */
        header {
            padding: 40px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f3f4f6;
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #111827 0%, #4b5563 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .brand div {
            font-size: 14px;
            color: #6b7280;
            margin-top: 4px;
            font-weight: 500;
        }

        .invoice-badge {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px 50px;
        }

        .info-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .info-content {
            font-size: 15px;
            line-height: 1.6;
        }
        
        .info-content strong {
            color: #111827;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        
        .info-label { color: #6b7280; }
        .info-value { font-weight: 600; color: #374151; }

        /* Table */
        .table-wrapper {
            padding: 0 50px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        th {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            padding: 16px;
            text-align: left;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            letter-spacing: 0.5px;
        }
        
        th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; border-left: 1px solid #e2e8f0; }
        th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; border-right: 1px solid #e2e8f0; text-align: right; }
        th.center { text-align: center; }

        td {
            padding: 16px;
            font-size: 15px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        td:last-child { text-align: right; font-weight: 600; color: #1e293b; }
        td.center { text-align: center; }
        
        tr:last-child td { border-bottom: none; }

        .item-name {
            font-weight: 600;
            color: #0f172a;
            display: block;
        }
        
        .section-title td {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            padding: 10px 16px;
            letter-spacing: 0.5px;
        }

        /* Totals Section */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            padding: 30px 50px 40px;
            background-color: #ffffff;
        }

        .totals-box {
            width: 350px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 15px;
            color: #64748b;
        }

        .total-row.final {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed #e2e8f0;
            font-size: 20px;
            color: #0f172a;
            font-weight: 700;
            align-items: center;
        }
        
        .payment-info {
            margin-top: 12px;
            font-size: 14px;
        }
        
        .payment-info .total-row {
            font-size: 14px;
            color: #94a3b8;
        }

        .total-label { font-weight: 500; }
        .total-value { font-weight: 600; }

        /* Footer */
        footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }

        .thank-you {
            font-size: 16px;
            font-weight: 600;
            color: #4f46e5;
            margin-bottom: 8px;
        }

        .meta-footer {
            font-size: 13px;
            color: #94a3b8;
        }

        /* Print Button */
        .actions {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 100;
        }
        
        .btn-print {
            background-color: #111827;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 50px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            font-size: 15px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            background-color: #000;
        }

        @media print {
            body { background-color: white; padding: 0; margin: 0; }
            .invoice-container { box-shadow: none; max-width: 100%; border-radius: 0; }
            .actions { display: none; }
            @page { margin: 0; size: auto; }
        }
    </style>
</head>
<body>

    <div class="actions">
        <button class="btn-print" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak Invoice
        </button>
    </div>

    <div class="invoice-container">
        <div class="top-bar"></div>
        
        <header>
            <div class="brand">
                <h1>Haen Komputer</h1>
                <div>Solusi Komputer Terpercaya</div>
            </div>
            <div class="invoice-badge">
                Invoice
            </div>
        </header>

        <div class="info-grid">
            <div class="info-card">
                <h3>Informasi Pelanggan</h3>
                <div class="info-content">
                    @if($penjualan->member)
                        <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 4px;">{{ $penjualan->member->nama_member }}</div>
                        <div style="color: #6b7280;">{{ $penjualan->member->no_telp ?? '-' }}</div>
                    @else
                        <div style="font-size: 18px; font-weight: 700; color: #111827;">Pelanggan Umum</div>
                        <div style="color: #6b7280;">-</div>
                    @endif
                </div>
            </div>
            <div class="info-card">
                <h3>Detail Transaksi</h3>
                <div class="info-content">
                    <div class="info-row">
                        <span class="info-label">No. Nota</span>
                        <span class="info-value">#{{ $penjualan->no_nota }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tanggal</span>
                        <span class="info-value">{{ \Carbon\Carbon::parse($penjualan->tanggal_penjualan)->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kasir</span>
                        <span class="info-value">{{ $penjualan->karyawan->nama_karyawan ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Pembayaran</span>
                        <span class="info-value" style="color: #6366f1;">{{ $penjualan->metode_bayar?->label() ?? 'Tunai' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="45%">Item Deskripsi</th>
                        <th class="center" width="15%">Qty</th>
                        <th style="text-align: right;" width="20%">Harga</th>
                        <th style="text-align: right;" width="20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <span class="item-name">{{ $item->produk->nama_produk ?? 'Item Terhapus' }}</span>
                            </td>
                            <td class="center">{{ $item->qty }}</td>
                            <td>Rp {{ number_format((float) ($item->harga_jual ?? 0), 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) ($item->qty * ($item->harga_jual ?? 0)), 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    @if($services->count())
                        <tr class="section-title">
                            <td colspan="4">Jasa & Layanan</td>
                        </tr>
                        @foreach($services as $service)
                            <tr>
                                <td>
                                    <span class="item-name">{{ $service->jasa->nama_jasa ?? 'Jasa' }}</span>
                                </td>
                                <td class="center">{{ $service->qty ?? 1 }}</td>
                                <td>Rp {{ number_format((float) ($service->harga ?? 0), 0, ',', '.') }}</td>
                                <td>Rp {{ number_format((float) (($service->qty ?? 1) * ($service->harga ?? 0)), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">Rp {{ number_format((float) $penjualan->total, 0, ',', '.') }}</span>
                </div>
                @if($penjualan->diskon_total > 0)
                <div class="total-row" style="color: #ef4444;">
                    <span class="total-label">Diskon</span>
                    <span class="total-value">- Rp {{ number_format((float) $penjualan->diskon_total, 0, ',', '.') }}</span>
                </div>
                @endif
                
                <div class="total-row final">
                    <span class="total-label">Total Tagihan</span>
                    <span class="total-value">Rp {{ number_format((float) $penjualan->grand_total, 0, ',', '.') }}</span>
                </div>

                <div class="payment-info">
                    <div class="total-row">
                        <span class="total-label">Tunai Diterima</span>
                        <span class="total-value">Rp {{ number_format((float) ($penjualan->tunai_diterima ?? 0), 0, ',', '.') }}</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Kembalian</span>
                        <span class="total-value">Rp {{ number_format((float) ($penjualan->kembalian ?? 0), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="thank-you">Terima Kasih Telah Berbelanja!</div>
            <div class="meta-footer">
                Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.<br>
                Simpan struk ini sebagai bukti pembayaran yang sah.
            </div>
        </footer>
    </div>

</body>
</html>
