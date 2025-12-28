<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $fileName }}</title>
    <style type="text/css" media="all">
        * {
            font-family: DejaVu Sans, sans-serif !important;
        }

        html {
            width: 100%;
        }

        .header {
            margin-bottom: 16px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }

        .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border-radius: 10px;
        }

        table td,
        th {
            border-color: #ededed;
            border-style: solid;
            border-width: 1px;
            font-size: 12px;
            overflow: hidden;
            padding: 8px 5px;
            word-break: normal;
        }

        table th {
            font-weight: 600;
            background: #1d4ed8;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="content">
        @php
            $sortedRows = isset($sort_key) ? $rows->sortBy($sort_key, SORT_NATURAL | SORT_FLAG_CASE) : $rows;
        @endphp

        <div class="header">
            <p class="title">{{ $title ?? $fileName }}</p>
            @if (! empty($subtitle))
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
                @foreach ($sortedRows as $row)
                    <tr>
                        @foreach ($columns as $column)
                            <td>
                                {{ $row[$column->getName()] }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
