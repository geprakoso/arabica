<?php

namespace App\Console\Commands;

use App\Models\Produk;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\WooCommerceService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class SyncWooCommerceInventory extends Command
{
    protected $signature = 'sync:woocommerce {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Reconcile local stock with WooCommerce inventory';

    protected WooCommerceService $wooService;

    protected int $synced = 0;

    protected int $failed = 0;

    protected int $skipped = 0;

    protected int $mismatches = 0;

    public function handle(): int
    {
        $this->wooService = new WooCommerceService;
        $dryRun = $this->option('dry-run');

        $this->info('Starting WooCommerce inventory reconciliation...');
        $this->line('Mode: '.($dryRun ? 'DRY RUN (no changes will be made)' : 'LIVE'));
        $this->line('');

        if (! $this->wooService->connect()) {
            $this->error('Failed to connect to WooCommerce API');

            return self::FAILURE;
        }

        $this->info('✓ Connected to WooCommerce');
        $this->line('');

        $produks = $this->getProductsToSync();

        if ($produks->isEmpty()) {
            $this->warn('No products with stock to sync');

            return self::SUCCESS;
        }

        $this->info("Found {$produks->count()} products with stock");
        $this->line('');

        $bar = $this->output->createProgressBar($produks->count());
        $bar->start();

        foreach ($produks as $produk) {
            $this->reconcileProduct($produk, $dryRun);
            $bar->advance();
        }

        $bar->finish();
        $this->line('');
        $this->line('');

        $this->displaySummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function getProductsToSync()
    {
        return Produk::withTrashed()
            ->whereHas('pembelianItems', function ($query) {
                $query->whereRaw('COALESCE(qty_sisa, 0) > 0');
            })
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->get();
    }

    protected function reconcileProduct(Produk $produk, bool $dryRun): void
    {
        if (blank($produk->sku)) {
            $this->skipped++;
            Log::debug('SyncWooCommerceInventory: Skipped - no SKU', ['produk_id' => $produk->id]);

            return;
        }

        $localStock = $this->calculateLocalStock($produk);

        try {
            $wooProduct = $this->wooService->getProductBySku($produk->sku);

            if (! $wooProduct) {
                $this->warn("Product not found in WooCommerce: {$produk->sku}");
                $this->skipped++;
                $this->logSync($produk->id, null, 'not_found', null, null, 'Product not found in WooCommerce');

                return;
            }

            $wooStock = (int) ($wooProduct['stock_quantity'] ?? 0);
            $wooStatus = $wooProduct['stock_status'] ?? 'unknown';

            if ($localStock === $wooStock) {
                $this->skipped++;
                Log::debug('SyncWooCommerceInventory: Stock matches', [
                    'produk_id' => $produk->id,
                    'sku' => $produk->sku,
                    'local_stock' => $localStock,
                    'woo_stock' => $wooStock,
                ]);

                return;
            }

            $this->mismatches++;

            if ($dryRun) {
                $this->line('');
                $this->info("  [DRY RUN] Would sync: {$produk->sku}");
                $this->info("    Local: {$localStock}, WooCommerce: {$wooStock}");

                return;
            }

            $result = $this->wooService->updateStock($wooProduct['id'], $localStock);

            $this->synced++;
            $this->logSync($produk->id, $wooProduct['id'], 'reconciled', $localStock, $result, null);

            Log::info('SyncWooCommerceInventory: Stock reconciled', [
                'produk_id' => $produk->id,
                'sku' => $produk->sku,
                'woo_product_id' => $wooProduct['id'],
                'previous_woo_stock' => $wooStock,
                'new_stock' => $localStock,
            ]);
        } catch (RequestException $e) {
            $this->failed++;
            $this->logSync($produk->id, null, 'error', null, null, $e->getMessage());

            Log::error('SyncWooCommerceInventory: Failed to reconcile', [
                'produk_id' => $produk->id,
                'sku' => $produk->sku,
                'error' => $e->getMessage(),
            ]);

            $this->error("Failed: {$produk->sku} - {$e->getMessage()}");
        }
    }

    protected function calculateLocalStock(Produk $produk): int
    {
        if ($produk->trashed()) {
            return 0;
        }

        return (int) $produk->pembelianItems()
            ->selectRaw('COALESCE(SUM(qty_sisa), 0) as total_stock')
            ->value('total_stock');
    }

    protected function logSync(int $produkId, ?int $wooProductId, string $action, ?int $stockQuantity, ?array $response, ?string $error): void
    {
        if (! class_exists(WooCommerceSyncLog::class)) {
            return;
        }

        try {
            WooCommerceSyncLog::create([
                'produk_id' => $produkId,
                'woo_product_id' => $wooProductId,
                'action' => $action,
                'request_payload' => $stockQuantity ? json_encode(['stock_quantity' => $stockQuantity]) : null,
                'response_payload' => $response ? json_encode($response) : null,
                'error_message' => $error,
                'synced_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('SyncWooCommerceInventory: Failed to log sync', [
                'produk_id' => $produkId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function displaySummary(): void
    {
        $this->info('=== Reconciliation Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Synced', $this->synced],
                ['Mismatches Found', $this->mismatches],
                ['Failed', $this->failed],
                ['Skipped (up-to-date)', $this->skipped],
            ]
        );
    }
}
