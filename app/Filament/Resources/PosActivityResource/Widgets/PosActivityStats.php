<?php

namespace App\Filament\Resources\PosActivityResource\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget;
use EightyNine\FilamentAdvancedWidget\AdvancedStatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class PosActivityStats extends AdvancedStatsOverviewWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $yesterdayStart = $todayStart->copy()->subDay();
        $yesterdayEnd = $yesterdayStart->copy()->endOfDay();

        $todayTransactions = $this->countTransactionsBetween($todayStart, $todayEnd);
        $yesterdayTransactions = $this->countTransactionsBetween($yesterdayStart, $yesterdayEnd);

        $todayRevenue = $this->sumRevenueBetween($todayStart, $todayEnd);
        $yesterdayRevenue = $this->sumRevenueBetween($yesterdayStart, $yesterdayEnd);

        $todayProducts = $this->sumProductsBetween($todayStart, $todayEnd);
        $yesterdayProducts = $this->sumProductsBetween($yesterdayStart, $yesterdayEnd);

        [$recentStart, $recentEnd] = $this->recentDaysRange();

        return [
            Stat::make('Transaksi Hari Ini', number_format($todayTransactions, 0, ',', '.'))
                ->description($this->formatDelta($todayTransactions, $yesterdayTransactions))
                ->descriptionIcon($this->resolveDeltaIcon($todayTransactions, $yesterdayTransactions))
                ->icon('heroicon-o-credit-card')
                ->iconColor('primary')
                ->chart($this->buildTransactionSeries($recentStart, $recentEnd))
                ->chartcolor('primary'),

            Stat::make('Pendapatan Hari Ini', $this->formatCurrency($todayRevenue))
                ->description($this->formatDelta($todayRevenue, $yesterdayRevenue))
                ->descriptionIcon($this->resolveDeltaIcon($todayRevenue, $yesterdayRevenue))
                ->icon('heroicon-o-banknotes')
                ->iconColor('success')
                ->chart($this->buildRevenueSeries($recentStart, $recentEnd))
                ->chartcolor('success'),

            Stat::make('Produk Terjual', number_format($todayProducts, 0, ',', '.'))
                ->description($this->formatDelta($todayProducts, $yesterdayProducts))
                ->descriptionIcon($this->resolveDeltaIcon($todayProducts, $yesterdayProducts))
                ->icon('heroicon-o-cube')
                ->iconColor('warning')
                ->chart($this->buildProductSeries($recentStart, $recentEnd))
                ->chartcolor('warning'),
        ];
    }

    protected function countTransactionsBetween(Carbon $start, Carbon $end): int
    {
        return (int) Penjualan::query()
            ->posOnly()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->count();
    }

    protected function sumRevenueBetween(Carbon $start, Carbon $end): int
    {
        return (int) Penjualan::query()
            ->posOnly()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->sum('grand_total');
    }

    protected function sumProductsBetween(Carbon $start, Carbon $end): int
    {
        $salesTable = (new Penjualan())->getTable();
        $itemsTable = (new PenjualanItem())->getTable();

        return (int) PenjualanItem::query()
            ->join($salesTable, "{$salesTable}.id_penjualan", '=', "{$itemsTable}.id_penjualan")
            ->where(function ($query) use ($salesTable): void {
                $query
                    ->where("{$salesTable}.sumber_transaksi", 'pos')
                    ->orWhereNull("{$salesTable}.sumber_transaksi");
            })
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->sum("{$itemsTable}.qty");
    }

    protected function buildTransactionSeries(Carbon $start, Carbon $end): array
    {
        $data = Penjualan::query()
            ->selectRaw('DATE(tanggal_penjualan) as day, COUNT(*) as total')
            ->posOnly()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    protected function buildRevenueSeries(Carbon $start, Carbon $end): array
    {
        $data = Penjualan::query()
            ->selectRaw('DATE(tanggal_penjualan) as day, SUM(grand_total) as total')
            ->posOnly()
            ->whereBetween('tanggal_penjualan', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    protected function buildProductSeries(Carbon $start, Carbon $end): array
    {
        $salesTable = (new Penjualan())->getTable();
        $itemsTable = (new PenjualanItem())->getTable();

        $data = PenjualanItem::query()
            ->join($salesTable, "{$salesTable}.id_penjualan", '=', "{$itemsTable}.id_penjualan")
            ->selectRaw("DATE({$salesTable}.tanggal_penjualan) as day, SUM({$itemsTable}.qty) as total")
            ->where(function ($query) use ($salesTable): void {
                $query
                    ->where("{$salesTable}.sumber_transaksi", 'pos')
                    ->orWhereNull("{$salesTable}.sumber_transaksi");
            })
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($data, $start, $end);
    }

    /**
     * @param  Collection<string, int>  $dataset
     */
    protected function fillDailySeries(Collection $dataset, Carbon $start, Carbon $end): array
    {
        $series = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $series[] = (int) ($dataset[$date->toDateString()] ?? 0);
        }

        return $series;
    }

    protected function recentDaysRange(int $days = 7): array
    {
        $end = now()->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        return [$start, $end];
    }

    protected function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }

    protected function formatDelta(int $current, int $previous): string
    {
        if ($previous <= 0) {
            return 'Tidak ada pembanding';
        }

        $delta = (($current - $previous) / $previous) * 100;
        $sign = $delta > 0 ? '+' : '';

        return $sign . number_format($delta, 1, ',', '.') . '% vs kemarin';
    }

    protected function resolveDeltaIcon(int $current, int $previous): ?string
    {
        if ($current === $previous) {
            return null;
        }

        return $current >= $previous
            ? 'heroicon-o-arrow-trending-up'
            : 'heroicon-o-arrow-trending-down';
    }
}
