@php
    // $jasa = $record->jasa;
    // $harga = $jasa?->harga ?? 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Service {{ $record->no_resi }}</title>
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
            padding: 20px;
            color: #374151; /* gray-700 */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-size: 14px; /* Standardize body font size */
        }

        /* Layout Container */
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px; /* Slightly less rounded than reference but clean */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            min-height: 1000px; /* Ensure A4 feel */
        }

        /* Top Border Line */
        .top-line {
            height: 6px;
            background: linear-gradient(90deg, #1e3a8a 0%, #10b981 100%); /* Blue to Green gradient like image */
            width: 100%;
        }

        /* Header */
        header {
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-info h1 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 5px 0;
            color: #111827; /* gray-900 */
        }

        .company-info p {
            font-size: 13px;
            color: #6b7280; /* gray-500 */
            margin: 2px 0;
            line-height: 1.5;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-title-badge {
            background-color: #2563eb; /* Blue badge */
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .invoice-details {
            font-size: 13px;
        }

        .detail-row {
            margin-bottom: 4px; /* More spacing */
            color: #6b7280;
        }
        .detail-row span {
            color: #111827;
            font-weight: 600; /* Revert to bold for emphasis */
            font-size: 13px;
        }

        /* Colored Divider */
        .colored-divider {
            height: 1px;
            background: linear-gradient(90deg, #1e3a8a 0%, #10b981 100%);
            margin: 0 40px;
            border: none;
            opacity: 0.2; /* Make it subtle */
        }

        /* Cards Section */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 30px 40px;
        }

        .info-card {
            background-color: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            padding: 24px; /* More padding */
        }

        .card-title {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }

        .card-content {
            font-size: 14px;
        }

        .customer-name {
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            font-size: 15px; /* Slightly larger */
            display: flex;
            justify-content: space-between;
        }

        .customer-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px; /* consistent spacing */
            color: #4b5563;
            font-size: 13px;
        }
        
        .customer-detail label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .customer-detail span {
            color: #1f2937;
            font-weight: 500;
        }

        /* Table */
        .table-section {
            padding: 0 40px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background-color: #1d4ed8; /* Strong Blue */
            color: white;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 12px 16px;
            text-align: left;
            letter-spacing: 0.5px;
        }
        
        thead th:first-child { border-top-left-radius: 6px; }
        thead th:last-child { border-top-right-radius: 6px; text-align: left; }

        tbody td {
            padding: 16px;
            font-size: 14px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151; /* gray-700 */
            vertical-align: top;
        }

        /* tbody td:nth-child(2) { text-align: center; } */
        /* tbody td:last-child { text-align: right; } */
        tbody td:last-child { text-align: left; }

        .section-header td {
            background-color: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            padding: 10px 16px;
        }

        .item-name {
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
            color: #111827; /* gray-900 */
        }
        
        .item-desc {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.4;
        }

        /* Totals */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            padding: 20px 40px;
        }

        /* Notes & Footer Grid */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 0 40px 40px;
            margin-top: 10px;
        }

        .note-box {
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            padding: 24px;
            min-height: 100px;
            background-color: #f9fafb; /* Consistent background */
        }

        .note-header {
            font-size: 11px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .note-content {
            font-size: 13px;
            color: #4b5563;
            line-height: 1.5;
        }

        /* Signatures */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 40px 40px 80px; /* More spacing */
        }

        .sig-box {
            text-align: center;
        }

        .sig-space {
            height: 80px;
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 12px;
        }

        .sig-title {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 24px;
        }
        
        .sig-name {
            font-weight: 700;
            font-size: 14px;
            color: #111827;
        }

        /* Bottom Footer Bar */
        .bottom-bar {
            background: linear-gradient(90deg, #1e3a8a 0%, #10b981 100%);
            color: white;
            text-align: center;
            padding: 16px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Print Override */
        @media print {
            body { 
                background-color: white; 
                padding: 0; 
                margin: 0; 
                font-size: 10pt; /* Smaller base font for print */
                -webkit-print-color-adjust: exact;
            }
            .invoice-container { 
                box-shadow: none; 
                border-radius: 0; 
                max-width: 100%; 
                min-height: auto; /* Allow flexible height */
                margin: 0;
            }
            .no-print { display: none; }
            .info-card, .note-box { border: 1px solid #e5e7eb; }
            
            /* Reset padding for print to save space */
            header, .cards-grid, .table-section, .bottom-grid, .signatures { padding-left: 20px; padding-right: 20px; }
            header { padding-top: 10px; padding-bottom: 10px; }
            
            @page { 
                margin: 0; 
                size: auto; /* Let printer driver handle size */
            }
        }

        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: #111827;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            z-index: 100;
        }
        
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">Cetak / Print</button>

    <div class="invoice-container">
        <!-- Decoration Line -->
        <div class="top-line"></div>

        <header>
            <div class="company-info">
                <h1>{{ $profile->name ?? 'Haen Komputer' }}</h1>
                <p>{{ $profile->address ?? 'Alamat belum diatur' }}</p>
                <p>Telp: {{ $profile->phone ?? '-' }} | Email: {{ $profile->email ?? '-' }}</p>
            </div>
            <div class="invoice-info">
                <div class="invoice-title-badge">INVOICE SERVICE</div>
                <div class="detail-row">No. Nota: <span>#{{ $record->no_resi }}</span></div>
                <div class="detail-row">Tanggal: <span>{{ $record->created_at->format('d/m/Y') }}</span></div>
                <div class="detail-row">Kasir: <span>{{ Auth::user()->name }}</span></div>
            </div>
        </header>

        <div class="colored-divider"></div>

        <div class="cards-grid">
            <!-- Informasi Pelanggan -->
            <div class="info-card">
                <div class="card-title">Informasi Pelanggan</div>
                <div class="customer-name">
                    {{ $record->member->nama_member }}
                </div>
                <div class="customer-detail">
                    <label>No. HP</label>
                    <span>{{ $record->member->no_hp }}</span>
                </div>
                <div class="customer-detail">
                    <label>Alamat</label>
                    <span style="text-align: right; max-width: 60%;">{{ $record->member->alamat ?? '-' }}</span>
                </div>
            </div>

            <!-- Rincian Layanan / Metode Bayar -->
            <div class="info-card">
                <div class="card-title">Informasi Layanan</div>
                <div class="customer-detail">
                    <label>Teknisi</label>
                    <span>{{ $record->technician ? explode(' ', $record->technician->name)[0] : '-' }}</span>
                </div>
                <div class="customer-detail">
                    <label>Estimasi Selesai</label>
                    <span>{{ $record->estimasi_selesai ? $record->estimasi_selesai->format('d/m/Y') : '-' }}</span>
                </div>
                <div class="customer-detail">
                    <label>Status</label>
                    @php
                        $statusLabel = match($record->status) {
                            'pending' => 'Menunggu Antrian',
                            'diagnosa' => 'Sedang Diagnosa',
                            'waiting_part' => 'Menunggu Sparepart',
                            'progress' => 'Sedang Dikerjakan',
                            'done' => 'Selesai',
                            'cancel' => 'Dibatalkan',
                            default => $record->status,
                        };
                    @endphp
                    <span style="font-weight: 700; color: #2563eb;">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>

        <div class="table-section">
            <table>
                <thead>
                    <tr>
                        <th width="100%">Nama Perangkat Service</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Section: Unit (Listed) -->
                    <tr>
                        <td>
                            <span class="item-name" style="margin-bottom: 4px;">{{ $record->nama_perangkat }}</span>
                            <span class="item-desc">{{ $record->kelengkapan }}</span>
                        </td>
                    </tr>

                    <!-- Section: Jasa -->
                    <tr class="section-header">
                        <td style="background-color: #f8fafc; color: #64748b; font-weight: 700; font-size: 12px; text-transform: uppercase; padding: 8px 15px;">Layanan Diambil</td>
                    </tr>
                    <tr>
                        <td>
                            <span class="item-name">{{ $record->jasa->nama_jasa ?? 'Service Umum' }}</span>
                            <span class="item-desc">{{ $record->jasa->sku ?? '' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Totals Removed per user request -->

        <div class="bottom-grid">
            <div class="note-box">
                <div class="note-header">CATATAN / KELUHAN</div>
                <div class="note-content">
                    {{ $record->keluhan ?? 'Tidak ada catatan tambahan.' }}
                    @if($record->catatan_teknisi)
                    <br><br>
                    <strong>Catatan Teknisi:</strong><br>
                    {{ $record->catatan_teknisi }}
                    @endif
                </div>
            </div>
            
            <!-- <div class="note-box" style="border: none;"> -->
                <!-- Placeholder for QR or extra info -->
            <!-- </div> -->
        </div>

        <div class="signatures">
            <div class="sig-box">
                <div class="sig-title">Tanda Tangan Kasir</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ Auth::user()->name }}</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Tanda Tangan Pelanggan</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $record->member->nama_member }}</div>
            </div>
        </div>

        <div class="bottom-bar">
            Terima kasih telah mempercayakan service kepada kami. Simpan invoice ini sebagai bukti pengambilan.
        </div>
    </div>

</body>
</html>
