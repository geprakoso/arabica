@php
    // $jasa = $record->jasa;
    // $harga = $jasa?->harga ?? 0;
    // Ensure profile variable doesn't crash if missing (though it appears used in existing code)
    // $profile = $profile ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Service {{ $record->no_resi }}</title>
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
            background-color: #4338ca; /* Indigo badge */
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

        /* Checklist Section */
        .checklist-section {
            padding: 0 40px;
            margin-bottom: 20px;
        }

        .section-box {
            background-color: #ffffff; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            padding: 20px;
            margin-bottom: 20px;
        }

        .section-header-title {
            font-weight: 800;
            font-size: 14px;
            color: #111827;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .checklist-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .category-title {
            font-weight: 700; 
            font-size: 12px; 
            color: #1f2937; 
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .category-title span.icon {
            margin-right: 6px;
            color: #6b7280;
        }

        .tags-container {
            display: flex; 
            flex-wrap: wrap; 
            gap: 8px;
            margin-bottom: 24px;
        }

        .tag {
            background-color: #f3f4f6; 
            border: 1px solid #d1d5db; 
            color: #374151; 
            font-size: 12px; 
            padding: 4px 10px; 
            border-radius: 6px;
            font-weight: 500;
        }
        
        .tag.empty {
            color: #9ca3af;
            background-color: transparent;
            border: 1px dashed #d1d5db;
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
            .info-card, .note-box, .section-box { border: 1px solid #e5e7eb; }
            
            /* Reset padding for print to save space */
            header, .cards-grid, .checklist-section, .signatures { padding-left: 20px; padding-right: 20px; }
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
                <div class="invoice-title-badge">CHECKLIST UNIT</div>
                <div class="detail-row">No. Nota: <span>#{{ $record->no_resi }}</span></div>
                <div class="detail-row">Tanggal: <span>{{ $record->created_at->format('d/m/Y') }}</span></div>
                <div class="detail-row">Teknisi: <span>{{ Auth::user()->name }}</span></div>
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
            </div>

            <!-- Rincian Unit -->
            <div class="info-card">
                <div class="card-title">Informasi Unit</div>
                <div class="customer-name">
                    {{ $record->nama_perangkat }}
                </div>
                <div class="customer-detail">
                    <label>Kelengkapan</label>
                    <span>{{ $record->kelengkapan }}</span>
                </div>
            </div>
        </div>

        <div class="checklist-section">
            <div class="section-box">
                <div class="section-header-title">Detail Kelengkapan & Crosscheck</div>
                
                @if($record->has_crosscheck)
                    <!-- Grid 2 Kolom -->
                    <div class="checklist-grid">
                        
                        <!-- Col 1 -->
                        <div>
                            <!-- Crosscheck -->
                            <div class="category-title">
                                <span>Kondisi Fisik / Crosscheck</span>
                            </div>
                            <div class="tags-container">
                                @forelse($record->crosschecks as $item)
                                    <span class="tag">{{ $item->name }}</span>
                                @empty
                                    <span class="tag empty">Tidak ada data</span>
                                @endforelse
                            </div>

                            <!-- OS -->
                            <div class="category-title">
                                <span>Sistem Operasi</span>
                            </div>
                            <div class="tags-container">
                                @forelse($record->listOs as $item)
                                    <span class="tag">{{ $item->name }}</span>
                                @empty
                                    <span class="tag empty">Tidak ada data</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Col 2 -->
                        <div>
                            <!-- Aplikasi -->
                            <div class="category-title">
                                <span>Aplikasi</span>
                            </div>
                            <div class="tags-container">
                                @forelse($record->listAplikasis as $item)
                                    <span class="tag">{{ $item->name }}</span>
                                @empty
                                    <span class="tag empty">Tidak ada data</span>
                                @endforelse
                            </div>

                            <!-- Game -->
                            <div class="category-title">
                                <span>Game</span>
                            </div>
                            <div class="tags-container">
                                @forelse($record->listGames as $item)
                                    <span class="tag">{{ $item->name }}</span>
                                @empty
                                    <span class="tag empty">Tidak ada data</span>
                                @endforelse
                            </div>
                        </div>

                    </div>
                @else
                    <div style="text-align: center; color: #6b7280; font-style: italic; padding: 40px;">
                        Data Crosscheck tidak diaktifkan untuk layanan ini.
                    </div>
                @endif
            </div>

        <div class="bottom-bar">
            Dokumen ini adalah bukti pengecekan resmi unit sebelum/sesudah pengerjaan.
        </div>
    </div>

</body>
</html>
