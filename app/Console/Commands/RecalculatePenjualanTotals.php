<?php

namespace App\Console\Commands;

use App\Models\Penjualan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecalculatePenjualanTotals extends Command
{
    protected $signature = 'penjualan:recalculate-totals {--from=} {--to=} {--only-with-jasa}';

    protected $description = 'Recalculate penjualan totals (items + jasa) and refresh payment status.';

    public function handle(): int
    {
        $query = Penjualan::query();

        $from = $this->option('from');
        $to = $this->option('to');

        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::minValue();
            $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::maxValue();

            $query->whereBetween('tanggal_penjualan', [$fromDate, $toDate]);
        }

        if ($this->option('only-with-jasa')) {
            $query->whereHas('jasaItems');
        }

        $total = 0;

        $query->orderBy('id_penjualan')
            ->chunkById(200, function ($rows) use (&$total): void {
                foreach ($rows as $penjualan) {
                    $penjualan->recalculateTotals();
                    $penjualan->recalculatePaymentStatus();
                    $total++;
                }
            }, 'id_penjualan');

        $this->info("Recalculated totals for {$total} penjualan.");

        return self::SUCCESS;
    }
}
