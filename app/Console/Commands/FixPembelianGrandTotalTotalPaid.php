<?php

namespace App\Console\Commands;

use App\Models\Pembelian;
use Illuminate\Console\Command;

class FixPembelianGrandTotalTotalPaid extends Command
{
    protected $signature = 'pembelian:fix-totals {--dry-run : Tampilkan tanpa simpan}';

    protected $description = 'Backfill grand_total dan total_paid di tb_pembelian yang masih 0';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $pembelians = Pembelian::with(['items', 'jasaItems', 'pembayaran'])->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($pembelians as $pembelian) {
            $expectedGrandTotal = (float) $pembelian->calculateTotalPembelian();
            $expectedTotalPaid = (float) $pembelian->pembayaran->sum('jumlah');

            $dbGrandTotal = (float) ($pembelian->grand_total ?? 0);
            $dbTotalPaid = (float) ($pembelian->total_paid ?? 0);

            $needsUpdate = false;

            if (abs($dbGrandTotal - $expectedGrandTotal) > 0.01) {
                $needsUpdate = true;
            }

            if (abs($dbTotalPaid - $expectedTotalPaid) > 0.01) {
                $needsUpdate = true;
            }

            if (! $needsUpdate) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                'ID %d | no_po: %s | grand_total: %.2f → %.2f | total_paid: %.2f → %.2f',
                $pembelian->id_pembelian,
                $pembelian->no_po,
                $dbGrandTotal,
                $expectedGrandTotal,
                $dbTotalPaid,
                $expectedTotalPaid,
            ));

            if (! $isDryRun) {
                $pembelian->forceFill([
                    'grand_total' => $expectedGrandTotal,
                    'total_paid' => $expectedTotalPaid,
                ])->saveQuietly();

                $pembelian->clearCalculationCache();
            }

            $fixed++;
        }

        if ($isDryRun) {
            $this->info("DRY RUN: {$fixed} record akan diupdate, {$skipped} sudah benar.");
        } else {
            $this->info("{$fixed} record diupdate, {$skipped} sudah benar.");
        }

        return self::SUCCESS;
    }
}