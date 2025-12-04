<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use EightyNine\FilamentAdvancedWidget\AdvancedChartWidget;
use Illuminate\Support\Facades\DB;

class MonthlyRevenueTrendChart extends AdvancedChartWidget
{
    protected static ?string $heading = 'Pendapatan 6 Bulan Terakhir';

    protected static ?string $description = 'Melihat tren kasir POS dari waktu ke waktu';

    protected static ?string $maxHeight = '320px';

    protected static ?string $icon = 'heroicon-o-chart-bar';  

    protected static ?string $iconColor = 'primary';

    protected static ?string $pollingInterval = null;

    protected int | string | array $columnSpan = 'full';


    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $end = now()->copy()->endOfMonth();
        $start = $end->copy()->subMonths(5)->startOfMonth();

        $totals = Penjualan::query()
            ->selectRaw('DATE_FORMAT(tanggal_penjualan, "%Y-%m") as bulan, SUM(grand_total) as total')
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($start, '1 month', $end) as $month) {
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M Y');
            $data[] = (float) ($totals[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $data,
                    'tension' => 0.4,
                    'fill' => 'start',
                    'borderWidth' => 2,
                    'backgroundColor' => 'rgba(59,130,246,0.15)',
                    'borderColor' => '#3b82f6',
                    'pointRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
