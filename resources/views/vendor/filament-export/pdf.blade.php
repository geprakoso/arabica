<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $fileName }}</title>
    <style type="text/css" media="all">
        @page {
            margin: 2cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            margin-bottom: 25px;
            border-bottom: 2px solid #2563eb;
            /* Changed to Blue */
            padding-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
            margin: 0;
        }

        .subtitle {
            font-size: 11px;
            color: #6b7280;
            margin: 4px 0 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 20px;
        }

        th {
            background-color: #f8fafc;
            color: #2563eb;
            /* Changed to Blue */
            text-transform: uppercase;
            font-size: 9px;
            font-weight: bold;
            padding: 10px 6px;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        td {
            padding: 8px 6px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        .group-header td {
            background-color: #dbeafe !important;
            /* Changed to Blue-100 */
            color: #1e40af;
            /* Changed to Blue-800 */
            font-weight: bold;
            padding: 8px 6px;
            font-size: 11px;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }

        .signature-box {
            float: right;
            width: 200px;
            text-align: center;
        }

        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #333;
        }

        .footer {
            margin-top: 30px;
            font-size: 9px;
            color: #94a3b8;
            text-align: right;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
            clear: both;
        }
    </style>
</head>

<body>
    @php
        $sortedRows = isset($sort_key) ? $rows->sortBy($sort_key, SORT_NATURAL | SORT_FLAG_CASE) : $rows;
        $colCount = $columns->count();
        $groupKey = $group_by ?? null;
        $groupedRows = $groupKey
            ? $sortedRows->groupBy(fn($row) => data_get($row, $groupKey, $row[$groupKey] ?? '-') ?: '-')
            : collect(['' => $sortedRows]);
        // $groupLabelPrefix = $group_label ?? 'Kategori';
    @endphp

    <div class="header">
        <h1 class="title">{{ $title ?? $fileName }}</h1>
        @if (!empty($subtitle))
            <p class="subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>
                        {{ $column->getLabel() }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($groupedRows as $groupLabel => $groupRows)
                @if ($groupKey)
                    <tr class="group-header">
                        <td colspan="{{ $colCount }}">
                            {{ $groupLabel }}
                        </td>
                    </tr>
                @endif
                @foreach ($groupRows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td>
                                {{ $row[$column->getName()] }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if (isset($summary) && count($summary) > 0)
        <table style="width: 100%; margin-top: 20px; page-break-inside: avoid; border: 1px solid #e2e8f0;">
            @foreach ($summary as $item)
                <tr>
                    <th style="border-right: 1px solid #e2e8f0; width: 60%;">
                        {{ $item['label'] ?? '' }}
                    </th>
                    <td style="text-align: right; font-weight: bold;">
                        {{ $item['value'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <p style="margin-bottom: 5px;">Disetujui Oleh,</p>
            <div class="signature-line"></div>
            <p style="margin-top: 5px;">( ...................................... )</p>
        </div>
    </div>

    <div class="footer">
        Dicetak oleh: {{ $printed_by ?? 'System' }} • {{ $printed_at ?? date('d/m/Y H:i') }}
    </div>
</body>

</html>
