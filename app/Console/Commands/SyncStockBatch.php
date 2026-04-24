<?php

namespace App\Console\Commands;

use App\Models\PembelianItem;
use App\Models\StockBatch;
use App\Models\StockMutation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStockBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-stock-batch 
                            {--dry-run : Preview changes without applying}
                            {--fix-missing : Create StockBatch for items without batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync qty_sisa from PembelianItem to StockBatch.qty_available';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking Stock Batch synchronization...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $fixMissing = $this->option('fix-missing');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be applied');
            $this->newLine();
        }

        // Get stats
        $totalItems = PembelianItem::count();
        $itemsWithBatch = PembelianItem::has('stockBatch')->count();
        $itemsWithoutBatch = PembelianItem::doesntHave('stockBatch')->count();

        $this->info("📊 Statistics:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total PembelianItem', $totalItems],
                ['With StockBatch', $itemsWithBatch],
                ['Without StockBatch', $itemsWithoutBatch],
            ]
        );

        $this->newLine();

        // Check sync status
        $this->info('🔍 Checking sync status...');
        
        $outOfSync = collect();
        
        PembelianItem::has('stockBatch')
            ->with('stockBatch')
            ->chunk(100, function ($items) use (&$outOfSync) {
                foreach ($items as $item) {
                    $qtySisa = (int) ($item->qty_sisa ?? $item->qty_masuk ?? $item->qty ?? 0);
                    $batchQty = $item->stockBatch->qty_available;
                    
                    if ($qtySisa !== $batchQty) {
                        $outOfSync->push([
                            'pembelian_item_id' => $item->id_pembelian_item,
                            'produk_id' => $item->id_produk ?? $item->produk_id,
                            'qty_sisa' => $qtySisa,
                            'batch_qty' => $batchQty,
                            'diff' => $qtySisa - $batchQty,
                        ]);
                    }
                }
            });

        if ($outOfSync->isEmpty()) {
            $this->info('✅ All StockBatch are in sync with PembelianItem.qty_sisa');
        } else {
            $this->warn("⚠️  Found {$outOfSync->count()} out of sync records:");
            $this->table(
                ['PembelianItem ID', 'Produk ID', 'qty_sisa', 'batch_qty', 'Diff'],
                $outOfSync->toArray()
            );

            if (!$dryRun && $this->confirm('Do you want to sync these records?')) {
                $this->syncOutOfSync($outOfSync);
            }
        }

        $this->newLine();

        // Fix missing batches
        if ($itemsWithoutBatch > 0 && $fixMissing) {
            $this->info('🔧 Creating missing StockBatch records...');
            
            if (!$dryRun) {
                $this->createMissingBatches();
            } else {
                $this->warn('Dry run - would create ' . $itemsWithoutBatch . ' missing batches');
            }
        }

        $this->newLine();
        $this->info('✅ Sync check complete!');

        return self::SUCCESS;
    }

    /**
     * Sync out of sync records
     */
    private function syncOutOfSync($records): void
    {
        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $record) {
            $item = PembelianItem::find($record['pembelian_item_id']);
            
            if (!$item || !$item->stockBatch) {
                continue;
            }

            DB::transaction(function () use ($item, $record) {
                $batch = $item->stockBatch;
                $oldQty = $batch->qty_available;
                $newQty = $record['qty_sisa'];
                
                // Update batch
                $batch->update(['qty_available' => $newQty]);
                
                // Create mutation log
                StockMutation::create([
                    'stock_batch_id' => $batch->id,
                    'type' => 'initial_sync',
                    'qty_change' => $newQty - $oldQty,
                    'qty_before' => $oldQty,
                    'qty_after' => $newQty,
                    'reference_type' => 'PembelianItem',
                    'reference_id' => $item->id_pembelian_item,
                    'notes' => "Sync from qty_sisa: {$oldQty} → {$newQty}",
                ]);
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Synced {$records->count()} records");
    }

    /**
     * Create missing StockBatch records
     */
    private function createMissingBatches(): void
    {
        $count = 0;
        
        PembelianItem::doesntHave('stockBatch')
            ->chunk(100, function ($items) use (&$count) {
                foreach ($items as $item) {
                    $qty = (int) ($item->qty_sisa ?? $item->qty_masuk ?? $item->qty ?? 0);
                    
                    if ($qty <= 0) {
                        continue;
                    }

                    DB::transaction(function () use ($item, $qty, &$count) {
                        $batch = StockBatch::create([
                            'pembelian_item_id' => $item->id_pembelian_item,
                            'produk_id' => $item->id_produk ?? $item->produk_id,
                            'qty_total' => $item->qty ?? $qty,
                            'qty_available' => $qty,
                        ]);

                        // Create mutation log
                        StockMutation::create([
                            'stock_batch_id' => $batch->id,
                            'type' => 'initial_sync',
                            'qty_change' => $qty,
                            'qty_before' => 0,
                            'qty_after' => $qty,
                            'reference_type' => 'PembelianItem',
                            'reference_id' => $item->id_pembelian_item,
                            'notes' => 'Initial sync - created missing batch',
                        ]);

                        $count++;
                    });
                }
            });

        $this->info("✅ Created {$count} missing StockBatch records");
    }
}
