<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;

class LaporanInputTransaksiStats extends AdvancedStatsOverviewWidget
{
    protected static ?string $heading = 'Ringkasan Keuangan (MTD)';

    protected static ?string $description = 'Pendapatan, beban, dan neto bulan berjalan vs bulan lalu.';

    protected static ?string $icon = 'heroicon-o-presentation-chart-line';

    protected static ?string $iconColor = 'primary';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->dateRanges();

        $currentRevenue = $this->sumByCategory(KategoriAkun::Pendapatan, $currentStart, $currentEnd);
        $previousRevenue = $this->sumByCategory(KategoriAkun::Pendapatan, $previousStart, $previousEnd);

        $currentExpense = $this->sumByCategory(KategoriAkun::Beban, $currentStart, $currentEnd);
        $previousExpense = $this->sumByCategory(KategoriAkun::Beban, $previousStart, $previousEnd);

        $currentNet = $currentRevenue - $currentExpense;
        $previousNet = $previousRevenue - $previousExpense;

        return [
            Stat::make('Pendapatan MTD', $this->formatCurrency($currentRevenue))
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->description($this->formatDelta($currentRevenue, $previousRevenue))
                ->descriptionIcon($this->deltaIcon($currentRevenue, $previousRevenue), 'before')
                ->descriptionColor($this->deltaColor($currentRevenue, $previousRevenue))
                ->chart($this->dailySeries(KategoriAkun::Pendapatan, $currentStart, $currentEnd))
                ->chartColor('success'),

            Stat::make('Beban MTD', $this->formatCurrency($currentExpense))
                ->icon('heroicon-o-receipt-refund')
                ->iconColor('danger')
                ->description($this->formatDelta($currentExpense, $previousExpense))
                ->descriptionIcon($this->deltaIcon($currentExpense, $previousExpense), 'before')
                ->descriptionColor($this->deltaColor($currentExpense, $previousExpense, positiveIsGood: false))
                ->chart($this->dailySeries(KategoriAkun::Beban, $currentStart, $currentEnd))
                ->chartColor('danger'),

            Stat::make('Neto MTD', $this->formatCurrency($currentNet))
                ->icon('heroicon-o-calculator')
                ->iconColor('primary')
                ->description($this->formatDelta($currentNet, $previousNet))
                ->descriptionIcon($this->deltaIcon($currentNet, $previousNet), 'before')
                ->descriptionColor($this->deltaColor($currentNet, $previousNet))
                ->chart($this->netSeries($currentStart, $currentEnd))
                ->chartColor('primary'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    protected function dateRanges(): array
    {
        $now = now();
        $currentStart = $now->copy()->startOfMonth();
        $currentEnd = $now->copy()->endOfDay();

        $previousStart = $currentStart->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $currentStart->copy()->subMonthNoOverflow()->endOfMonth();

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    protected function sumByCategory(KategoriAkun $category, Carbon $start, Carbon $end): float
    {
        return (float) InputTransaksiToko::query()
            ->where('kategori_transaksi', $category)
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->sum('nominal_transaksi');
    }

    protected function dailySeries(KategoriAkun $category, Carbon $start, Carbon $end): array
    {
        return array_values($this->dailyTotals($category, $start, $end));
    }

    protected function netSeries(Carbon $start, Carbon $end): array
    {
        $income = $this->dailyTotals(KategoriAkun::Pendapatan, $start, $end);
        $expense = $this->dailyTotals(KategoriAkun::Beban, $start, $end);

        $series = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->toDateString();
            $series[] = ($income[$key] ?? 0) - ($expense[$key] ?? 0);
        }

        return $series;
    }

    protected function dailyTotals(KategoriAkun $category, Carbon $start, Carbon $end): array
    {
        $raw = InputTransaksiToko::query()
            ->selectRaw('DATE(tanggal_transaksi) as day, SUM(nominal_transaksi) as total')
            ->where('kategori_transaksi', $category)
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $series = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->toDateString();
            $series[$key] = (float) ($raw[$key] ?? 0);
        }

        return $series;
    }

    protected function formatCurrency(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    protected function formatDelta(float $current, float $previous): string
    {
        if ($previous <= 0.0) {
            return 'Tidak ada pembanding bulan lalu';
        }

        if ($current === $previous) {
            return 'Stabil vs bulan lalu';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $sign = $delta > 0 ? '+' : '';

        return $sign . number_format($delta, 1, ',', '.') . '% vs bulan lalu';
    }

    protected function deltaIcon(float $current, float $previous): ?string
    {
        if ($previous <= 0.0 || $current === $previous) {
            return null;
        }

        return $current > $previous
            ? 'heroicon-o-arrow-trending-up'
            : 'heroicon-o-arrow-trending-down';
    }

    protected function deltaColor(float $current, float $previous, bool $positiveIsGood = true): string
    {
        if ($previous <= 0.0 || $current === $previous) {
            return 'gray';
        }

        $isUp = $current > $previous;

        return $isUp === $positiveIsGood ? 'success' : 'danger';
    }
}
