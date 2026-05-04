<?php

namespace App\Console\Commands;

use App\Models\PembelianItem;
use App\Models\StockBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncQtySisaFromStockBatch extends Command
{
    protected $signature = 'stock:sync-qty-sisa {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Sinkronkan PembelianItem.qty_sisa dari StockBatch.qty_available (one-time migration)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $col = PembelianItem::qtySisaColumn();

        $this->info($dryRun ? '🔍 DRY RUN — tidak ada perubahan yang disimpan.' : '🔄 Sinkronisasi dimulai...');
        $this->newLine();

        $batches = StockBatch::with('pembelianItem.produk')->get();
        $fixed = 0;
        $skipped = 0;

        foreach ($batches as $batch) {
            $pi = $batch->pembelianItem;
            if (! $pi) {
                $skipped++;

                continue;
            }

            $currentSisa = (int) $pi->{$col};
            $expected = max(0, (int) $batch->qty_available);

            if ($currentSisa !== $expected) {
                $produk = $pi->produk?->nama_produk ?? 'N/A';
                $this->line(sprintf(
                    '  ✏️  PembelianItem#%d (%s): %s %d → %d',
                    $pi->id_pembelian_item,
                    $produk,
                    $col,
                    $currentSisa,
                    $expected
                ));

                if (! $dryRun) {
                    $pi->{$col} = $expected;
                    $pi->saveQuietly();
                }

                $fixed++;
            }
        }

        $this->newLine();

        if ($fixed === 0) {
            $this->info('✅ Semua data sudah sinkron. Tidak ada perubahan.');
        } else {
            $verb = $dryRun ? 'perlu diperbaiki' : 'diperbaiki';
            $this->info("✅ {$fixed} record {$verb}. {$skipped} batch dilewati (tidak ada PembelianItem).");
        }

        if ($dryRun && $fixed > 0) {
            $this->newLine();
            $this->warn('Jalankan tanpa --dry-run untuk menerapkan perubahan.');
        }

        return self::SUCCESS;
    }
}
