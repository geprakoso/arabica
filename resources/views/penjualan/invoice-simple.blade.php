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
    $invoiceDate = $penjualan->tanggal_penjualan
        ? $penjualan->tanggal_penjualan->format('d.m.Y')
        : now()->format('d.m.Y');
    $dueDate = $penjualan->tanggal_penjualan
        ? $penjualan->tanggal_penjualan->format('d.m.Y')
        : now()->format('d.m.Y');
    $qrUrl = 'https://store.haen.co.id/';
    $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(96)->margin(0)->generate($qrUrl);
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
        if (! empty($metaParts)) {
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
    $hasTransfer = $metodeBayarValue === 'transfer'
        || $payments->pluck('metode_bayar')
            ->filter()
            ->contains(function ($method): bool {
                $value = $method instanceof \App\Enums\MetodeBayar ? $method->value : (string) $method;

                return $value === 'transfer';
            });
    $transferAccounts = $payments
        ->filter(fn($payment) => ($payment->metode_bayar instanceof \App\Enums\MetodeBayar
                ? $payment->metode_bayar->value
                : (string) $payment->metode_bayar) === 'transfer')
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice {{ $penjualan->no_nota }}</title>
        <style>
            @page {
                size: A5;
                margin: 7mm;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                padding: 0;
                font-family: "Helvetica Neue", Arial, sans-serif;
                color: #111827;
                background-color: #ffffff;
            }

            .page {
                max-width: 148mm;
                margin: 0 auto;
            }

            .top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 24px;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 14px;
                font-size: 13px;
                color: #4b5563;
                max-width: 60%;
            }

            .logo-box {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background-color: #e5e7eb;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                color: #374151;
            }


            .brand-text p {
                margin: 2px 0;
            }

            .header-meta {
                text-align: right;
                font-size: 12px;
                color: #4b5563;
            }

            .qr {
                margin-top: 12px;
                align-items: end;
            }

            .qr svg {
                width: 96px;
                height: 96px;
            }

            .divider {
                margin: 20px 0 28px;
                border-top: 2px solid #4b5563;
            }

            .title {
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 4px;
            }

            .subtitle {
                font-size: 13px;
                color: #6b7280;
                margin-bottom: 28px;
            }

            
            .info-grid {
                display: grid;
                margin-top: -20px;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 24px;
                margin-bottom: 10px;
                font-size: 12px;
                
            }

            .info-block h4 {
                font-size: 11px;
                letter-spacing: 1.5px;
                margin: 0 0 10px;
                text-transform: uppercase;
                color: #6b7280;
            }

            .info-block p {
                margin: 4px 0;
                color: #374151;
                line-height: 1.5;
            }

            .info-block.pelanggan p,
            .info-block.metode p {
                line-height: 1.3;
            }

            .table {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px;
            }

            .table th {
                text-transform: uppercase;
                letter-spacing: 2px;
                font-weight: 600;
                font-size: 11px;
                text-align: left;
                padding: 10px 0;
                border-bottom: 1px solid #9ca3af;
            }

            .table td {
                padding: 12px 0;
                border-bottom: 1px solid #e5e7eb;
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

            .summary {
                margin-top: 24px;
                display: flex;
                justify-content: flex-end;
            }

            .summary table {
                width: 260px;
                font-size: 13px;
            }

            .summary td {
                padding: 6px 0;
            }

            .summary .label {
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #6b7280;
                font-size: 11px;
            }

            .summary .total {
                font-weight: 700;
                font-size: 15px;
            }

            .signature {
                margin-top: 56px;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 24px;
                font-size: 12px;
                color: #6b7280;
            }

            .notice {
                max-width: 320px;
                font-size: 10px;
                color: #6b7280;
                line-height: 1;
            }

            .signature .qr {
                margin-left: auto;
                text-align: center;
            }

            .notice p {
                margin: 0 0 4px;
            }

            .notice .notice-title {
                margin-top: 6px;
                font-weight: 700;
                color: #4b5563;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="top">
                <div class="brand">
                    <div class="logo-box">
                        @if ($profileLogo)
                            <img src="{{ $profileLogo }}" alt="Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
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
                    <p><strong>{{ $memberName }}</strong></p>
                    @if ($memberAddress)
                        <p>{{ $memberAddress }}</p>
                    @endif
                    @if ($memberPhone)
                        <p>{{ $memberPhone }}</p>
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
                                    {{ $account->nama_bank ?? '-' }}{{ $account->no_rekening ? ' (' . $account->no_rekening . ')' : '' }}@if (! $loop->last), @endif
                                @endforeach
                            @else
                                {{ $penjualan->akunTransaksi?->nama_bank ?? '-' }}{{ $penjualan->akunTransaksi?->no_rekening ? ' (' . $penjualan->akunTransaksi?->no_rekening . ')' : '' }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>

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
                            <td>{!! $row['desc'] !!}</td>
                            <td class="unit">Rp {{ number_format($row['unit'], 0, ',', '.') }}</td>
                            <td class="qty">{{ number_format($row['qty'], 0, ',', '.') }}</td>
                            <td class="total">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">Tidak ada item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

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
                    <p>Kerusakan yang terjadi akibat human error (penggunaan tidak sesuai, jatuh, terkena air, dll) tidak ditanggung oleh garansi.</p>
                    <p class="notice-title">Kebijakan Pengembalian</p>
                    <p>Barang yang sudah dibeli tidak dapat dikembalikan. Periksa kondisi produk sebelum pembayaran.</p>
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
