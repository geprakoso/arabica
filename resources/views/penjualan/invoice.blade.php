@php
    $items = $penjualan->items ?? collect();
    $services = $penjualan->jasaItems ?? collect();
    $storeName = 'Haen Komputer';
    $storeAddress = $profile?->address ?? 'Alamat toko belum diisi';
    $storePhone = $profile?->phone;
    $storeEmail = $profile?->email;
    $memberName = $penjualan->member?->nama_member ?? 'Pelanggan Umum';
    $subtotalProduk = (float) $items->sum(fn($item) => (float) ($item->qty ?? 0) * (float) ($item->harga_jual ?? 0));
    $subtotalJasa = (float) $services->sum(
        fn($service) => (float) ($service->qty ?? 0) * (float) ($service->harga ?? 0),
    );
    $subtotal = $subtotalProduk + $subtotalJasa;
    $diskon = (float) ($penjualan->diskon_total ?? 0);
    $grandTotal = max(0, $subtotal - $diskon);
    $payments = $penjualan->pembayaran ?? collect();
    $totalPaid = (float) $payments->sum('jumlah');
    $statusRaw = $penjualan->status_pembayaran ?? null;
    $sisa = max(0, $grandTotal - $totalPaid);
    if ($statusRaw === 'lunas') {
        $sisa = 0;
    }
    $statusPembayaran =
        $statusRaw === 'belum_lunas'
            ? 'Belum Lunas'
            : ($statusRaw === 'lunas'
                ? 'Lunas'
                : ($sisa > 0
                    ? 'Belum Lunas'
                    : 'Lunas'));
    if ($totalPaid > 0) {
        $statusPembayaran = $sisa > 0 ? 'Belum Lunas' : 'Lunas';
    }
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
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 32px 16px;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 18px;
            box-shadow: 0 20px 30px -10px rgba(15, 23, 42, 0.18);
            overflow: hidden;
        }

        .top-bar {
            height: 10px;
            background: linear-gradient(90deg, #0f172a 0%, #1d4ed8 50%, #22c55e 100%);
        }

        header {
            padding: 32px 40px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 6px;
            letter-spacing: -0.3px;
        }

        .brand .store-meta {
            font-size: 14px;
            color: #64748b;
            line-height: 1.5;
        }

        .invoice-meta {
            text-align: right;
            font-size: 14px;
            color: #475569;
        }

        .invoice-meta .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 999px;
            background-color: #1d4ed8;
            color: #ffffff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            padding: 32px 40px 10px;
        }

        .info-card {
            background-color: #f8fafc;
            padding: 18px 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .info-card h3 {
            font-size: 11px;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1px;
            margin: 0 0 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            color: #334155;
            margin-bottom: 6px;
        }

        .info-label {
            color: #64748b;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
        }

        .table-wrapper {
            padding: 10px 40px 0;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 6px;
        }

        th {
            background-color: #1d4ed8;
            color: #f8fafc;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            padding: 14px;
            text-align: left;
            letter-spacing: 0.6px;
        }

        th:last-child,
        td:last-child {
            text-align: right;
        }

        th.center,
        td.center {
            text-align: center;
        }

        td {
            padding: 14px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }

        .item-meta {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .section-title td {
            background-color: #f1f5f9;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            font-size: 12px;
            letter-spacing: 0.6px;
            text-align: left;

        }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            padding: 24px 40px 10px;
        }

        .totals-box {
            width: 360px;
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid #e2e8f0;
        }


        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #475569;
        }

        .total-row.final {
            margin-top: 10px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5f5;
            font-size: 18px;
            color: #0f172a;
            font-weight: 700;
        }

        .details-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            padding: 0 40px 24px;
        }

        .detail-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 20px;
        }

        .detail-card.note-card {
            max-width: 660px;
            /* width: 530px; */
        }

        .detail-card.payment-card {
            max-width: 660px;
        }

        .detail-card h4 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #94a3b8;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            padding: 0 40px 32px;
        }

        .signature-box {
            border: 1px dashed #cbd5f5;
            border-radius: 12px;
            padding: 16px 18px 28px;
            text-align: center;
            color: #475569;
        }

        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-weight: 600;
            color: #0f172a;
        }

        footer {
            background: linear-gradient(90deg, #0f172a 0%, #1d4ed8 50%, #22c55e 100%);
            color: #e2e8f0;
            padding: 20px 32px;
            text-align: center;
            font-size: 13px;
        }

        .actions {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 100;
        }

        .btn-print {
            background-color: #0f172a;
            color: #ffffff;
            border: none;
            padding: 12px 22px;
            border-radius: 999px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 600;
            box-shadow: 0 12px 20px -8px rgba(15, 23, 42, 0.4);
            transition: transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 860px) {
            .totals-box {
                width: 100%;
            }
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .invoice-container {
                border-radius: 0;
                box-shadow: none;
                width: 210mm;
            }

            .actions {
                display: none;
            }

            @page {
                size: A4;
                margin: 12mm;
            }
        }
    </style>
</head>

<body>
    <div class="actions">
        <button class="btn-print" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            Cetak Invoice
        </button>
    </div>

    <div class="invoice-container">
        <div class="top-bar"></div>
        <header>
            <div class="brand">
                <h1>{{ $storeName }}</h1>
                <div class="store-meta">
                    {{ $storeAddress }}<br>
                    @if ($storePhone)
                        Telp: {{ $storePhone }}
                    @endif
                    @if ($storeEmail)
                        <span> | Email: {{ $storeEmail }}</span>
                    @endif
                </div>
            </div>
            <div class="invoice-meta">
                <div class="badge">Invoice Penjualan</div>
                <div>No. Nota: <strong>#{{ $penjualan->no_nota }}</strong></div>
                <div>Tanggal: {{ $penjualan->tanggal_penjualan?->format('d/m/Y') }}</div>
                <div>Kasir: {{ $penjualan->karyawan->nama_karyawan ?? '-' }}</div>
            </div>
        </header>

        <div class="info-grid">
            <div class="info-card">
                <h3>Informasi Pelanggan</h3>
                <div class="info-row">
                    <span class="info-label">Nama</span>
                    <span class="info-value">{{ $memberName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. HP</span>
                    <span class="info-value">{{ $penjualan->member?->no_hp ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Alamat</span>
                    <span class="info-value">{{ $penjualan->member?->alamat ?? '-' }}</span>
                </div>
            </div>

            <div class="info-card">
                <h3>Rincian Metode Bayar</h3>
                <div class="info-row">
                    <span class="info-label">Metode</span>
                    <span class="info-value">
                        @if ($payments->count() > 0)
                            @php
                                $methodLabels = $payments
                                    ->pluck('metode_bayar')
                                    ->filter()
                                    ->map(
                                        fn($method) => match ($method) {
                                            'cash' => 'Tunai',
                                            'transfer' => 'Transfer',
                                            default => strtoupper((string) $method),
                                        },
                                    )
                                    ->unique()
                                    ->values();
                            @endphp
                            {{ $methodLabels->implode(' + ') ?: 'Split' }}
                        @else
                            {{ $penjualan->metode_bayar?->label() ?? 'Tunai' }}
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">{{ $statusPembayaran }}</span>
                </div>
                @if ((string) ($penjualan->metode_bayar?->value ?? ($penjualan->metode_bayar ?? '')) === 'transfer')
                    <div class="info-row">
                        <span class="info-label">Akun Transaksi</span>
                        <span class="info-value">
                            {{ $penjualan->akunTransaksi?->nama_bank ?? '-' }}
                            @if ($penjualan->akunTransaksi?->no_rekening)
                                ({{ $penjualan->akunTransaksi?->no_rekening }})
                            @endif
                        </span>
                    </div>
                @endif
                @if ((string) ($penjualan->metode_bayar?->value ?? ($penjualan->metode_bayar ?? '')) === 'cash')
                    <div class="info-row">
                        {{-- <span class="info-label">Tunai Diterima</span>
                        <span class="info-value">Rp
                            {{ number_format((float) ($penjualan->tunai_diterima ?? 0), 0, ',', '.') }}</span> --}}
                    </div>
                    <div class="info-row">
                        {{-- <span class="info-label">Kembalian</span>
                        <span class="info-value">Rp
                            {{ number_format((float) ($penjualan->kembalian ?? 0), 0, ',', '.') }}</span> --}}
                    </div>
                @else
                    {{-- <div class="info-row">
                        <span class="info-label">Tunai Diterima</span>
                        <span class="info-value">-</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kembalian</span>
                        <span class="info-value">-</span>
                    </div> --}}
                @endif
            </div>

            {{-- <div class="info-card">
                <h3>Lokasi Toko</h3>
                <div class="info-row">
                    <span class="info-label">Alamat</span>
                    <span class="info-value">{{ $storeAddress }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kontak</span>
                    <span class="info-value">{{ $storePhone ?? '-' }}</span>
                </div>
            </div> --}}
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th width="32%">Produk</th>
                        <th width="14%">SN</th>
                        <th width="12%">Garansi</th>
                        <th class="center" width="10%">Qty</th>
                        <th width="16%">Harga</th>
                        <th width="16%">Sub total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->produk->nama_produk ?? 'Item Terhapus' }}</strong>
                                {{-- @if ($item->pembelianItem?->pembelian?->no_po)
                                    <div class="item-meta">Batch: {{ $item->pembelianItem->pembelian->no_po }}</div>
                                @endif --}}
                            </td>
                            @php
                                $serials = is_array($item->serials ?? null) ? $item->serials : [];
                                $snList = collect($serials)->pluck('sn')->filter()->values();
                                $garansiList = collect($serials)->pluck('garansi')->filter()->values();
                            @endphp
                            <td>{{ $snList->isNotEmpty() ? $snList->implode(', ') : $item->produk->sn ?? '-' }}</td>
                            <td>
                                @if ($garansiList->isNotEmpty())
                                    {{ $garansiList->implode(', ') }}
                                @else
                                    {{ $item->produk->garansi ?? '-' }}
                                @endif
                            </td>
                            <td class="center">{{ $item->qty }}</td>
                            <td>Rp {{ number_format((float) ($item->harga_jual ?? 0), 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) ($item->qty * ($item->harga_jual ?? 0)), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    @if ($services->count())
                        <tr class="section-title">
                            <td colspan="6">Jasa & Layanan</td>
                        </tr>
                        @foreach ($services as $service)
                            <tr>
                                <td><strong>{{ $service->jasa->nama_jasa ?? 'Jasa' }}</strong></td>
                                <td>-</td>
                                <td>-</td>
                                <td class="center">{{ $service->qty ?? 1 }}</td>
                                <td>Rp {{ number_format((float) ($service->harga ?? 0), 0, ',', '.') }}</td>
                                <td>Rp
                                    {{ number_format((float) (($service->qty ?? 1) * ($service->harga ?? 0)), 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>

        <div class="totals-section">
            <div class="totals-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                @if ($diskon > 0)
                    <div class="total-row" style="color:#ef4444;">
                        <span>Diskon</span>
                        <span>- Rp {{ number_format($diskon, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="total-row">
                    <span>Total Dibayar</span>
                    <span>Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>
                </div>
                <div class="total-row final">
                    <span>Total Tagihan</span>
                    <span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
                <div class="total-row">
                    <span>Sisa Bayar</span>
                    <span>Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="details-row">
            <div class="detail-card note-card">
                <h4>Catatan</h4>
                @if ($penjualan->catatan)
                    <div class="note-content">{!! $penjualan->catatan !!}</div>
                @else
                    <div>Tidak ada catatan tambahan.</div>
                @endif
            </div>
            @if ($payments->count())
                <div class="detail-card payment-card">
                    <h4>Pembayaran</h4>
                    @foreach ($payments as $payment)
                        @php
                            $methodLabel = match ($payment->metode_bayar) {
                                'cash' => 'Tunai',
                                'transfer' => 'Transfer',
                                default => strtoupper((string) $payment->metode_bayar),
                            };
                            $accountLabel = null;
                            if ($payment->metode_bayar === 'transfer') {
                                $accountLabel = $payment->akunTransaksi?->nama_bank;
                                if ($payment->akunTransaksi?->no_rekening) {
                                    $accountLabel .= ' (' . $payment->akunTransaksi->no_rekening . ')';
                                }
                            }
                        @endphp
                        <div class="info-row">
                            <span class="info-label">{{ $methodLabel }}</span>
                            <span class="info-value">Rp
                                {{ number_format((float) ($payment->jumlah ?? 0), 0, ',', '.') }}</span>
                        </div>
                        @if ($accountLabel)
                            <div class="info-row">
                                <span class="info-label">Akun</span>
                                <span class="info-value">{{ $accountLabel }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>


        <div class="signature-grid">
            <div class="signature-box">
                Tanda Tangan Kasir
                <div class="signature-line">{{ $penjualan->karyawan->nama_karyawan ?? 'Kasir' }}</div>
            </div>
            <div class="signature-box">
                Tanda Tangan Pelanggan
                <div class="signature-line">{{ $memberName }}</div>
            </div>
        </div>

        <footer>
            Terima kasih telah berbelanja. Simpan invoice ini sebagai bukti pembayaran yang sah.
        </footer>
    </div>
</body>

</html>
