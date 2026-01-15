@php
    $penjualan = $tukarTambah->penjualan;
    $pembelian = $tukarTambah->pembelian;
    $items = $penjualan?->items ?? collect();
    $services = $penjualan?->jasaItems ?? collect();
    $purchaseItems = $pembelian?->items ?? collect();
    $storeName = 'Haen Komputer';
    $storeAddress = $profile?->address ?? 'Alamat toko belum diisi';
    $storePhone = $profile?->phone;
    $storeEmail = $profile?->email;
    $memberName = $penjualan?->member?->nama_member ?? 'Pelanggan Umum';
    $subtotalProduk = (float) $items->sum(fn($item) => (float) ($item->qty ?? 0) * (float) ($item->harga_jual ?? 0));
    $subtotalJasa = (float) $services->sum(
        fn($service) => (float) ($service->qty ?? 0) * (float) ($service->harga ?? 0),
    );
    $subtotalPenjualan = $subtotalProduk + $subtotalJasa;
    $subtotalPembelian = (float) $purchaseItems->sum(
        fn($item) => (float) ($item->qty ?? 0) * (float) ($item->hpp ?? 0),
    );
    $selisihTotal = $subtotalPenjualan - $subtotalPembelian;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Tukar Tambah {{ $tukarTambah->no_nota }}</title>
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

        footer {
            background: linear-gradient(90deg, #0f172a 0%, #1d4ed8 50%, #22c55e 100%);
            color: #e2e8f0;
            padding: 20px 32px;
            text-align: center;
            font-size: 13px;
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
            border-radius: 14px;
            padding: 16px;
        }

        .info-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }


        .info-card h3 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .info-card p {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }

        .section-title {
            display: inline-block;
            font-size: 15px;
            font-weight: 700;
            background-color: #1d4ed8;
            border-radius: 10px;
            padding: 8px 15px 8px 15px;
            margin: 24px 40px 8px;
            color: #ffffff;
        }

        table {
            width: calc(100% - 80px);
            margin: 0 40px 24px;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead th {
            text-align: left;
            background-color: #f8fafc;
            padding: 12px 14px;
            color: #475569;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
            border-bottom: 1px solid #e2e8f0;
        }

        tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;

        }


        td:nth-child(2),
        td:nth-child(3),
        td:nth-child(4),
        td:nth-child(5),
        td:nth-child(6) {
            font-weight: 400;
        }

        .text-right {
            text-align: right;
        }


        .summary {
            display: grid;
            grid-template-columns: repeat(1, minmax(220px, 1fr));
            gap: 16px;
            padding: 0 40px 32px;
        }

        .summary-card {
            background-color: #f8fafc;
            border-radius: 14px;
            justify-self: end;
            padding: 20px;
            min-width: 320px;
        }

        .summary-card h4 {
            margin: 0 0 16px;
            font-size: 14px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 14px;
            margin-top: 6px;
        }

        .summary-label {
            font-size: 14px;
            color: #475569;
            font-weight: 500;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 500;
            color: #0f172a;
        }

        .summary-row:nth-child(3) .summary-label,
        .summary-row:nth-child(3) .summary-value {
            color: #dc2626;
        }



        .summary-row:last-child .summary-label {
            color: #1d4ed8;
            font-weight: 700;
        }

        .summary-row:last-child .summary-value {
            color: #1d4ed8;
            font-size: 20px;
        }

        .details-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            padding: 0 40px 32px;
        }

        .detail-card {
            background-color: #f8fafc;
            border-radius: 14px;
            padding: 16px;
        }

        .detail-card h4 {
            margin: 0 0 12px;
            font-size: 13px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
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

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .footer-note {
            padding: 24px 40px 32px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 13px;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }

            .actions {
                display: none;
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
                    <div>{{ $storeAddress }}</div>
                    @if ($storePhone)
                        <div>{{ $storePhone }}</div>
                    @endif
                    @if ($storeEmail)
                        <div>{{ $storeEmail }}</div>
                    @endif
                </div>
            </div>
            <div class="invoice-meta">
                <div class="badge">Invoice Tukar Tambah</div>
                <div>No. Nota: <strong>{{ $tukarTambah->no_nota ?? '-' }}</strong></div>
                <div>Tanggal: {{ optional($tukarTambah->tanggal)->format('d F Y') ?? '-' }}</div>
                {{-- <div>Nota Penjualan: {{ $penjualan?->no_nota ?? '-' }}</div>
                <div>Nota Pembelian: {{ $pembelian?->no_po ?? '-' }}</div> --}}
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
                <h3>Metode Pembayaran</h3>
                <div class="info-row">
                    <span class="info-label">Kasir</span>
                    <span class="info-value">{{ $tukarTambah->karyawan?->nama_karyawan ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Bayar</span>
                    <span class="info-value">Lunas</span>
                </div>
            </div>
            {{-- <div class="info-card">
                <h3>Supplier</h3>
                <p>{{ $pembelian?->supplier?->nama_supplier ?? '-' }}</p>
            </div> --}}
        </div>

        <div class="section-title">Barang Dibeli</div>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Harga Beli</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->produk?->nama_produk ?? '-' }}</td>
                        <td class="text-right">{{ (int) ($item->qty ?? 0) }}</td>
                        <td class="text-right">Rp {{ number_format((int) ($item->harga_jual ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">Rp
                            {{ number_format((int) ($item->qty ?? 0) * (int) ($item->harga_jual ?? 0), 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada produk dijual.</td>
                    </tr>
                @endforelse
            </tbody>
            <thead>
                <tr>
                    <th>Jasa</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td>{{ $service->jasa?->nama_jasa ?? '-' }}</td>
                        <td class="text-right">{{ (int) ($service->qty ?? 0) }}</td>
                        <td class="text-right">Rp {{ number_format((int) ($service->harga ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">Rp
                            {{ number_format((int) ($service->qty ?? 0) * (int) ($service->harga ?? 0), 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada jasa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- <div class="section-title">Jasa</div>
        <table>
        </table> --}}

        <div class="section-title">Barang Dijual</div>
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">HPP</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchaseItems as $item)
                    <tr>
                        <td>{{ $item->produk?->nama_produk ?? '-' }}</td>
                        <td class="text-right">{{ (int) ($item->qty ?? 0) }}</td>
                        <td class="text-right">Rp {{ number_format((int) ($item->hpp ?? 0), 0, ',', '.') }}</td>
                        <td class="text-right">Rp
                            {{ number_format((int) ($item->qty ?? 0) * (int) ($item->hpp ?? 0), 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada barang dibeli.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-card">
                <h4>Ringkasan Pembayaran</h4>
                <div class="summary-row">
                    <span class="summary-label">Total Pembelian</span>
                    <span class="summary-value">Rp {{ number_format((int) $subtotalPenjualan, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Penjualan</span>
                    <span class="summary-value">- Rp {{ number_format((int) $subtotalPembelian, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Total Bayar</span>
                    <span class="summary-value">Rp {{ number_format((int) $selisihTotal, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <footer>
            Terima kasih telah berbelanja. Simpan invoice ini sebagai bukti pembayaran yang sah.
        </footer>
    </div>
</body>

</html>
