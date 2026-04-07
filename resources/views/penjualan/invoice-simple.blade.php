@php
    $items = $penjualan->items ?? collect();
    $services = $penjualan->jasaItems ?? collect();
    $profileName = $profile?->name ?? 'Haen Komputer';
    $profileAddress = $profile?->address ?? 'Alamat toko belum diisi';
    $profilePhone = $profile?->phone;
    $profileEmail = $profile?->email;
    $profileLogo = $profile?->logo;
    $memberName = $penjualan->member?->nama_member ?? 'Pelanggan Umum';
    $memberAddress = $penjualan->member?->alamat;
    $memberPhone = $penjualan->member?->no_hp;
    $profileLogoUrl = null;
    if ($profileLogo) {
        $profileLogoUrl = \Illuminate\Support\Str::startsWith($profileLogo, ['http://', 'https://', '/'])
            ? $profileLogo
            : \Illuminate\Support\Facades\Storage::url($profileLogo);
    }
    $invoiceDate = $penjualan->tanggal_penjualan
        ? $penjualan->tanggal_penjualan->format('d.m.Y')
        : now()->format('d.m.Y');
    $dueDate = $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d.m.Y') : now()->format('d.m.Y');
    $qrUrl = 'https://store.haen.co.id/';
    $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(72)->margin(0)->generate($qrUrl);
    $payments = $penjualan->pembayaran ?? collect();
    $paymentLabel = null;
    if ($payments->count() > 0) {
        $methodLabels = $payments
            ->pluck('metode_bayar')
            ->filter()
            ->map(function ($method): string {
                $value = $method instanceof \App\Enums\MetodeBayar ? $method->value : (string) $method;

                return match ($value) {
                    'cash' => 'Tunai',
                    'transfer' => 'Transfer',
                    default => strtoupper($value),
                };
            })
            ->unique()
            ->values();
        $paymentLabel = $methodLabels->implode(' + ') ?: 'Split';
    } else {
        $paymentLabel = $penjualan->metode_bayar?->label() ?? 'Tunai';
    }

    $rows = collect();

    foreach ($items as $item) {
        $name = $item->produk?->nama_produk ?? 'Produk';
        if ($item->kondisi) {
            $name .= ' (' . $item->kondisi . ')';
        }

        $serials = is_array($item->serials ?? null) ? $item->serials : [];
        $snList = collect($serials)->pluck('sn')->filter()->values();
        $garansiList = collect($serials)->pluck('garansi')->filter()->values();

        $metaParts = [];
        if ($snList->isNotEmpty()) {
            $metaParts[] = 'SN: ' . $snList->implode(', ');
        }
        if ($garansiList->isNotEmpty()) {
            $metaParts[] = 'Garansi: ' . $garansiList->implode(', ');
        }

        $desc = $name;
        if (!empty($metaParts)) {
            $desc .= '<br><small>' . implode(' • ', $metaParts) . '</small>';
        }

        $qty = (float) ($item->qty ?? 0);
        $unit = (float) ($item->harga_jual ?? 0);
        $rows->push([
            'desc' => $desc,
            'qty' => $qty,
            'unit' => $unit,
            'total' => $qty * $unit,
        ]);
    }

    foreach ($services as $service) {
        $name = $service->jasa?->nama_jasa ?? 'Jasa';
        $qty = (float) ($service->qty ?? 0);
        $unit = (float) ($service->harga ?? 0);
        $rows->push([
            'desc' => $name,
            'qty' => $qty,
            'unit' => $unit,
            'total' => $qty * $unit,
        ]);
    }

    $subtotal = (float) $rows->sum('total');
    $diskon = (float) ($penjualan->diskon_total ?? 0);
    $grandTotal = max(0, $subtotal - $diskon);
    $metodeBayarValue = (string) ($penjualan->metode_bayar?->value ?? ($penjualan->metode_bayar ?? ''));
    $hasTransfer =
        $metodeBayarValue === 'transfer' ||
        $payments
            ->pluck('metode_bayar')
            ->filter()
            ->contains(function ($method): bool {
                $value = $method instanceof \App\Enums\MetodeBayar ? $method->value : (string) $method;

                return $value === 'transfer';
            });
    $transferAccounts = $payments
        ->filter(
            fn($payment) => ($payment->metode_bayar instanceof \App\Enums\MetodeBayar
                ? $payment->metode_bayar->value
                : (string) $payment->metode_bayar) === 'transfer',
        )
        ->map(fn($payment) => $payment->akunTransaksi)
        ->filter()
        ->unique('id')
        ->values();
    $totalPaid = (float) ($payments->sum('jumlah') ?? 0);
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
    <meta name="viewport" content="width=device-width,k initial-scale=1.0">
    <title>Invoice {{ $penjualan->no_nota }}</title>
    <style>
        @page {
            size: 241mm 137mm;
            margin: 2mm 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, "Helvetica Neue", sans-serif;
            font-size: 13px;
            line-height: 1.2;
            color: #111111;
            background-color: #ffffff;
        }

        .invoice {
            width: 241mm;
            height: 137mm;
            /* padding: 0 1mm; */

            /* overflow: hidden; */
        }


        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #000000;
            max-width: 60%;
        }


        .logo-box {
            width: 17%;
            height: 17%;
            rotate: -5deg;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #111111;
        }


        .brand-text p {
            margin: 1px 0;
        }

        .header-meta {
            text-align: right;
            font-size: 13px;
            color: #111111;
        }

        .qr {
            margin-top: 6px;
            align-items: end;
        }

        .qr svg {
            width: 72px;
            height: 72px;
        }

        .divider {
            margin: -1px 0 4px;
            border-top: 1px dashed #111111;
        }

        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 13px;
            color: #111111;
            margin-bottom: 12px;
        }


        .info-grid {
            display: grid;
            margin-top: 0px;
            padding: 0;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 4px;
            font-size: 13px;

        }

        .info-block h4 {
            font-size: 12px;
            letter-spacing: 1.5px;
            margin: 0 0 6px;
            text-transform: uppercase;
            color: #111111;
        }

        .info-block p {
            margin: 2px 0;
            color: #111111;
            line-height: 1.2;
        }

        .info-block.pelanggan p,
        .info-block.metode p {
            line-height: 1.1;
            margin-top: 1px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: -4px;
        }

        .table th {
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            font-size: 12px;
            text-align: left;
            padding: 6px 0;
            border-bottom: 1px dashed #111111;
        }

        .table td {
            padding: 2px 1px;
            line-height: 1.1;
            padding-right: 5px;
            /* border-bottom: 1px dashed #111111; */
        }

        .table td.item {
            padding-top: 2px;
            padding-bottom: 1px;
            padding-left: 5px;
            padding-right: 5px;
        }

        .table td.item small {
            display: block;
            margin-top: -2px;
            line-height: 1.05;
        }

        .table td.qty,
        .table td.unit,
        .table td.total,
        .table th.qty,
        .table th.unit,
        .table th.total {
            text-align: right;
            white-space: nowrap;
        }


        .summary-divider {
            margin-top: 4px;
            border-top: 1px dashed #111111;
        }

        .summary {
            margin-top: 2px;
            display: flex;
            justify-content: flex-end;
        }

        .summary table {
            width: 260px;
            font-size: 13px;
        }

        .summary td {
            padding: 1px 0;
        }

        .summary .label {
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111111;
            font-size: 11px;
        }

        .summary .total {
            font-weight: 700;
            font-size: 15px;
        }

        .signature {
            margin-top: 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            color: #111111;
        }

        .notice {
            max-width: 400px;
            font-size: 11px;
            color: #111111;
            line-height: 1.1;
        }

        .customer-sign {
            text-align: center;
            min-width: 140px;

        }

        .customer-sign .line {
            width: 120px;
            margin: 80px auto 6px;
            border-bottom: 1px dashed #111111;
        }

        /*
        .customer-sign .name {
            margin-top: 12px;
        } */

        .signature .qr {
            text-align: center;
        }

        .notice p {
            margin: 0 0 2px;
        }

        .notice .notice-title {
            margin-top: 4px;
            font-weight: 700;
            color: #111111;
        }

        @media print {
            body {
                font-size: 15px;
                color: #000000;
            }

            .brand {
                /* Nama brand */
                font-size: 17px;
            }

            .header-meta {
                /* Nomor invoice & tanggal */
                font-size: 15px;
            }

            .title {
                /* Judul invoice */
                font-size: 26px;
            }

            .subtitle {
                /* Subjudul */
                font-size: 15px;
            }

            .info-grid {
                /* Info pelanggan & metode */
                font-size: 15px;
                margin-bottom: 10px;
            }

            .info-block h4 {
                /* Label info (judul kecil) */
                font-size: 14px;
            }

            .pelanggan-name {
                /* Nama pelanggan */
                font-size: 15px;
            }

            .pelanggan-address {
                /* Alamat pelanggan */
                font-size: 12px;
                line-height: 1;
                margin-top: -2px;
            }

            .pelanggan-phone {
                /* No HP pelanggan */
                font-size: 12px;
                line-height: 1;
                margin-top: -2px;
            }

            .table {
                /* Tabel item */
                font-size: 15px;
                line-height: 0.5;
                margin-top: -2px;
                margin-right: 5px;
            }


            .table th {
                /* Header tabel */
                font-size: 14px;
                padding-left: 5px;
                padding-right: 5px;


            }

            .summary table {
                /* Ringkasan subtotal/diskon/total */
                font-size: 15px;
                line-height: 1;
                margin-right: 5px;
            }

            .summary .label {
                /* Label ringkasan */
                font-size: 13px;
            }

            .summary .total {
                /* Total akhir */
                font-size: 17px;
            }

            .signature {
                /* Area tanda tangan */
                font-size: 14px;
                margin-top: 3px;
                line-height: 0.6;
                margin-right: 5px;
            }

            .notice {
                /* Catatan tambahan */
                font-size: 12px;
                line-height: 0.9;
                margin-left: 5px;
            }

            .invoice {
                width: 241mm;
                height: 137mm;
                padding-top: 2mm;
                /* margin-top: 2mm;
                /* padding-right: 2mm; */
                */
                /* overflow: hidden; */
            }

            .brand,
            .brand-text p {
                color: #000000;
            }

            .brand-text p {
                /* Alamat & kontak */
                font-size: 15px;
                line-height: 1;
                margin: 0px;
            }

            .brand-text p:first-child {
                /* Nama brand (baris pertama) */
                font-size: 17px;
            }

            .divider,
            .table th,
            .table td {
                border-color: #000000;
            }
        }
    </style>
</head>

<body>
    <div class="invoice">
        <div class="top">
            <div class="brand">

                <div class="logo-box">
                    @if ($profileLogoUrl)
                        <img src="{{ $profileLogoUrl }}" alt="Logo"
                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                    @else
                        {{ strtoupper(substr($profileName, 0, 1)) }}
                    @endif
                </div>

                <div class="brand-text">
                    <p><strong>{{ $profileName }}</strong></p>
                    <p>{{ $profileAddress }}</p>
                    @if ($profilePhone)
                        <p>{{ $profilePhone }}</p>
                    @endif
                </div>
            </div>
            <div class="header-meta">
                <div><strong>Invoice #</strong> {{ $penjualan->no_nota }}</div>
                <div><strong>Tanggal</strong> {{ $invoiceDate }}</div>
            </div>
        </div>

        <div class="divider"></div>


        <div class="info-grid">
            <div class="info-block pelanggan">
                {{-- <h4>Pelanggan </h4> --}}
                <p class="pelanggan-name"><strong>{{ $memberName }}</strong></p>
                @if ($memberAddress)
                    <p class="pelanggan-address">{{ $memberAddress }}</p>
                @endif
                @if ($memberPhone)
                    <p class="pelanggan-phone">{{ $memberPhone }}</p>
                @endif
            </div>
            <div class="info-block metode">
                {{-- <h4>Metode Pembayaran</h4> --}}
                <p>{{ $paymentLabel ?: 'Belum ditentukan' }}</p>
                <p>Status: {{ $statusPembayaran }}</p>
                @if ($hasTransfer)
                    <p>
                        Akun :
                        @if ($transferAccounts->isNotEmpty())
                            @foreach ($transferAccounts as $account)
                                {{ $account->nama_bank ?? '-' }}{{ $account->no_rekening ? ' (' . $account->no_rekening . ')' : '' }}
                                @if (!$loop->last)
                                    ,
                                @endif
                            @endforeach
                        @else
                            {{ $penjualan->akunTransaksi?->nama_bank ?? '-' }}{{ $penjualan->akunTransaksi?->no_rekening ? ' (' . $penjualan->akunTransaksi?->no_rekening . ')' : '' }}
                        @endif
                    </p>
                @endif
            </div>
        </div>
        <div class="divider"></div>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="unit">Harga</th>
                    <th class="qty">Qty</th>
                    <th class="total">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="item">{!! $row['desc'] !!}</td>
                        <td class="unit">Rp {{ number_format($row['unit'], 0, ',', '.') }}</td>
                        <td class="qty">{{ number_format($row['qty'], 0, ',', '.') }}</td>
                        <td class="total">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="item" colspan="4">Tidak ada item.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary-divider"></div>

        <div class="summary">
            <table>
                <tr>
                    <td class="label">Subtotal</td>
                    <td style="text-align: right;">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Diskon</td>
                    <td style="text-align: right;">Rp {{ number_format($diskon, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label total">Total</td>
                    <td class="total" style="text-align: right;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="signature">
            <div class="notice">
                <strong>PERHATIAN</strong>
                <p class="notice-title">Cara Klaim Garansi</p>
                <p>Nota pembelian harus dibawa saat melakukan klaim garansi.</p>
                <p class="notice-title">Kerusakan Akibat Human Error</p>
                <p>Kerusakan yang terjadi akibat human error (penggunaan tidak sesuai, jatuh, terkena air, dll) tidak
                    ditanggung oleh garansi.</p>
                <p class="notice-title">Kebijakan Pengembalian</p>
                <p>Barang yang sudah dibeli tidak dapat dikembalikan. Periksa kondisi produk sebelum pembayaran.</p>
            </div>
            <div class="customer-sign">
                <div>Tanda tangan pelanggan</div>
                <div class="line"></div>
                <div class="name">{{ $memberName }}</div>
            </div>
            <div class="qr">
                {!! $qrSvg !!}
                <div style="margin-top: 12px;">Hormat kami,</div>
                <div style="margin-top: 6px;">{{ $profileName }}</div>
            </div>
        </div>
    </div>
</body>

</html>
