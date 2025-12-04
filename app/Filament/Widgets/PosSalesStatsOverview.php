<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class PosSalesStatsOverview extends AdvancedStatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        [$currentStart, $currentEnd] = $this->currentMonthRange();
        [$previousStart, $previousEnd] = $this->previousMonthRange($currentStart);

        $currentRevenue = $this->sumRevenueBetween($currentStart, $currentEnd);
        $previousRevenue = $this->sumRevenueBetween($previousStart, $previousEnd);

        $currentQty = $this->sumProductQtyBetween($currentStart, $currentEnd);
        $previousQty = $this->sumProductQtyBetween($previousStart, $previousEnd);

        $currentMemberCount = $this->countMembersBetween($currentStart, $currentEnd);
        $previousMemberCount = $this->countMembersBetween($previousStart, $previousEnd);

        return [
            Stat::make('Pendapatan Bulan Ini', $this
                ->formatCurrency($currentRevenue))
                ->icon('heroicon-o-banknotes')
                ->iconColor('primary')
                ->chart([10, 30, 25, 40, 35, 60])
                ->chart($this->buildDailyRevenueSeries($currentStart, $currentEnd))
                ->description($this->formatDelta($currentRevenue, $previousRevenue))
                ->descriptionIcon($this->resolveDeltaIcon($currentRevenue, $previousRevenue))
                ->chartcolor('primary'),
            Stat::make('Produk Terjual', number_format($currentQty, 0, ',', '.'))
                ->icon('heroicon-o-cube')
                ->iconColor('warning')
                ->chart([10, 30, 25, 40, 35, 60])
                ->chart($this->buildDailyProductSeries($currentStart, $currentEnd))
                ->description($this->formatDelta($currentQty, $previousQty))
                ->descriptionIcon($this->resolveDeltaIcon($currentQty, $previousQty))
                ->chartcolor('warning'),
            Stat::make('Member Aktif', number_format($currentMemberCount, 0, ',', '.'))
                ->icon('heroicon-o-users')
                ->iconColor('primary')
                ->chart([10, 30, 25, 40, 35, 60])
                ->chart($this->buildDailyMemberSeries($currentStart, $currentEnd))
                ->description($this->formatDelta($currentMemberCount, $previousMemberCount))
                ->descriptionIcon($this->resolveDeltaIcon($currentMemberCount, $previousMemberCount))
                ->chartcolor('primary'),
        ];
    }

    protected function sumRevenueBetween(Carbon $start, Carbon $end): float
    {
        return (float) Penjualan::query()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->sum('grand_total');
    }

    protected function sumProductQtyBetween(Carbon $start, Carbon $end): int
    {
        $salesTable = (new Penjualan())->getTable();
        $itemsTable = (new PenjualanItem())->getTable();

        return (int) PenjualanItem::query()
            ->join($salesTable, "{$salesTable}.id_penjualan", '=', "{$itemsTable}.id_penjualan")
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->sum("{$itemsTable}.qty");
    }

    protected function countMembersBetween(Carbon $start, Carbon $end): int
    {
        return (int) Penjualan::query()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->whereNotNull('id_member')
            ->distinct('id_member')
            ->count('id_member');
    }

    protected function buildDailyRevenueSeries(Carbon $start, Carbon $end): array
    {
        $data = Penjualan::query()
            ->selectRaw('DATE(tanggal_penjualan) as day, SUM(grand_total) as total')
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    protected function buildDailyProductSeries(Carbon $start, Carbon $end): array
    {
        $salesTable = (new Penjualan())->getTable();
        $itemsTable = (new PenjualanItem())->getTable();

        $data = PenjualanItem::query()
            ->join($salesTable, "{$salesTable}.id_penjualan", '=', "{$itemsTable}.id_penjualan")
            ->selectRaw("DATE({$salesTable}.tanggal_penjualan) as day, SUM({$itemsTable}.qty) as total")
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    protected function buildDailyMemberSeries(Carbon $start, Carbon $end): array
    {
        $data = Penjualan::query()
            ->selectRaw('DATE(tanggal_penjualan) as day, COUNT(DISTINCT id_member) as total')
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->whereNotNull('id_member')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    /**
     * @param  Collection<string, float|int>  $dataset
     */
    protected function fillDailySeries(Collection $dataset, Carbon $start, Carbon $end): array
    {
        $series = [];
        $effectiveEnd = $end->copy();

        if ($effectiveEnd->greaterThan(now())) {
            $effectiveEnd = now();
        }

        foreach (CarbonPeriod::create($start, $effectiveEnd) as $date) {
            $key = $date->toDateString();
            $series[] = (float) ($dataset[$key] ?? 0);
        }

        return $series;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function currentMonthRange(): array
    {
        $now = now();

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function previousMonthRange(Carbon $reference): array
    {
        $start = $reference->copy()->subMonth()->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    protected function formatCurrency(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    protected function formatDelta(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return 'Tidak ada pembanding bulan lalu';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $sign = $delta > 0 ? '+' : '';

        return $sign . number_format($delta, 1, ',', '.') . '% dibanding bulan lalu';
    }

    protected function resolveDeltaIcon(float $current, float $previous): ?string
    {
        if ($current === $previous) {
            return null;
        }

        return $current >= $previous
            ? 'heroicon-o-arrow-trending-up'
            : 'heroicon-o-arrow-trending-down';
    }
}
