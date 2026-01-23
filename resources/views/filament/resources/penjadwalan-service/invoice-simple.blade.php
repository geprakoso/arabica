@php
    $profileName = $profile?->name ?? 'Haen Komputer';
    $profileAddress = $profile?->address ?? 'Alamat toko belum diisi';
    $profilePhone = $profile?->phone;
    $profileEmail = $profile?->email;
    $profileLogo = $profile?->logo;
    $profileLogoUrl = null;
    if ($profileLogo) {
        $profileLogoUrl = \Illuminate\Support\Str::startsWith($profileLogo, ['http://', 'https://', '/'])
            ? $profileLogo
            : \Illuminate\Support\Facades\Storage::url($profileLogo);
    }

    $memberName = $record->member?->nama_member ?? 'Pelanggan';
    $memberAddress = $record->member?->alamat;
    $memberPhone = $record->member?->no_hp;
    $invoiceDate = $record->created_at ? $record->created_at->format('d.m.Y') : now()->format('d.m.Y');
    $queueNumber =
        \App\Models\PenjadwalanService::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $record->created_at)
            ->count() + 1;
    $qrUrl = 'https://store.haen.co.id/';
    $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(72)->margin(0)->generate($qrUrl);

    $statusLabel = match ($record->status) {
        'pending' => 'Menunggu Antrian',
        'diagnosa' => 'Sedang Diagnosa',
        'waiting_part' => 'Menunggu Sparepart',
        'progress' => 'Sedang Dikerjakan',
        'done' => 'Selesai',
        'cancel' => 'Dibatalkan',
        default => $record->status,
    };

    $serviceRows = [
        ['label' => 'Perangkat', 'value' => $record->nama_perangkat ?: '-'],
        ['label' => 'Kelengkapan', 'value' => $record->kelengkapan ?: '-'],
        ['label' => 'Keluhan', 'value' => $record->keluhan ?: '-'],
    ];

@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,k initial-scale=1.0">
    <title>Invoice {{ $record->no_resi }}</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 14mm;
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
            width: 100%;
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
            width: 60px;
            height: 60px;
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

        .divider {
            margin: 4px 0;
            border-top: 1px dashed #111111;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 13px;
        }

        .info-block p {
            margin: 2px 0;
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
        }

        .table td.item {
            padding-top: 2px;
            padding-bottom: 1px;
            padding-left: 5px;
            padding-right: 5px;
            font-weight: 600;
            width: 160px;
        }

        .table td.detail {
            padding-left: 4px;
        }

        .footer {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
        }

        .footer .qr {
            text-align: center;
        }

        .footer .qr svg {
            width: 72px;
            height: 72px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .logo-box {
                width: 50%;
                height: 30%;
                rotate: -5deg;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                color: #111111;
            }

            .brand-text p {
                font-size: 12px;
                color: #111111;
            }

            .brand-text p strong {
                font-size: 14px;
                color: #111111;
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
                    @if ($profileEmail)
                        <p>{{ $profileEmail }}</p>
                    @endif
                </div>
            </div>
            <div class="header-meta">
                <div><strong>Invoice #</strong> {{ $record->no_resi }}</div>
                <div><strong>No. Antrian</strong> {{ $queueNumber }}</div>
                <div><strong>Tanggal</strong> {{ $invoiceDate }}</div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="info-grid">
            <div class="info-block pelanggan">
                <p class="pelanggan-name"><strong>{{ $memberName }}</strong></p>
                @if ($memberAddress)
                    <p class="pelanggan-address">{{ $memberAddress }}</p>
                @endif
                @if ($memberPhone)
                    <p class="pelanggan-phone">{{ $memberPhone }}</p>
                @endif
            </div>
            <div class="info-block metode">
                <p><strong>Teknisi</strong>: {{ $record->technician?->name ?? '-' }}</p>
                <p><strong>Estimasi</strong>: {{ $record->estimasi_selesai?->format('d.m.Y') ?? '-' }}</p>
                <p><strong>Layanan</strong>: {{ $record->jasa?->nama_jasa ?? 'Service Umum' }}</p>
            </div>
        </div>

        <div class="divider"></div>

        <table class="table">
            <thead>
                <tr>
                    <th>Rincian Service</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($serviceRows as $row)
                    <tr>
                        <td class="item">{{ $row['label'] }}</td>
                        <td class="detail">{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer">
            <div class="qr">
                {!! $qrSvg !!}
                <div style="margin-top: 12px;">Hormat kami,</div>
                <div style="margin-top: 6px;">{{ $profileName }}</div>
            </div>
        </div>
    </div>
</body>

</html>
