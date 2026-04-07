<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use EightyNine\FilamentAdvancedWidget\AdvancedChartWidget;

class LaporanInputTransaksiTrendChart extends AdvancedChartWidget
{
    protected static ?string $heading = '30 Hari: Pendapatan vs Beban';

    protected static ?string $description = 'Memantau arus kas masuk/keluar harian untuk kesiapan closing.';

    protected static ?string $maxHeight = '320px';

    protected static ?string $icon = 'heroicon-o-chart-bar-square';

    protected static ?string $iconColor = 'primary';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $end = now()->endOfDay();
        $start = $end->copy()->subDays(29)->startOfDay();

        $raw = InputTransaksiToko::query()
            ->selectRaw('DATE(tanggal_transaksi) as day, kategori_transaksi, SUM(nominal_transaksi) as total')
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->whereIn('kategori_transaksi', [KategoriAkun::Pendapatan, KategoriAkun::Beban])
            ->groupBy('day', 'kategori_transaksi')
            ->orderBy('day')
            ->get();

        $income = [];
        $expense = [];

        foreach ($raw as $row) {
            $category = $row->kategori_transaksi instanceof KategoriAkun
                ? $row->kategori_transaksi
                : KategoriAkun::tryFrom($row->kategori_transaksi);

            if (! $category) {
                continue;
            }

            if ($category === KategoriAkun::Pendapatan) {
                $income[$row->day] = (int) $row->total;
            }

            if ($category === KategoriAkun::Beban) {
                $expense[$row->day] = (int) $row->total;
            }
        }

        $labels = [];
        $incomeSeries = [];
        $expenseSeries = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->toDateString();

            $labels[] = $date->format('d M');
            $incomeSeries[] = (int) ($income[$key] ?? 0);
            $expenseSeries[] = (int) ($expense[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $incomeSeries,
                    'tension' => 0.35,
                    'fill' => 'start',
                    'borderWidth' => 2,
                    'backgroundColor' => 'rgba(16,185,129,0.15)',
                    'borderColor' => '#10b981',
                    'pointRadius' => 3,
                ],
                [
                    'label' => 'Beban',
                    'data' => $expenseSeries,
                    'tension' => 0.35,
                    'fill' => 'start',
                    'borderWidth' => 2,
                    'backgroundColor' => 'rgba(239,68,68,0.12)',
                    'borderColor' => '#ef4444',
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
