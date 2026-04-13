<?php

namespace App\Jobs;

use App\Models\Produk;
use App\Services\WooCommerce\WooCommerceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStockToWooCommerce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 60;

    public int $produkId;

    public function __construct(int $produkId)
    {
        $this->produkId = $produkId;
        $this->onQueue('woocommerce');
    }

    public function handle(): void
    {
        $produk = Produk::withTrashed()->find($this->produkId);

        if (! $produk) {
            Log::warning('SyncStockToWooCommerce: Produk not found', ['produk_id' => $this->produkId]);

            return;
        }

        if (blank($produk->sku)) {
            Log::warning('SyncStockToWooCommerce: Produk has no SKU', ['produk_id' => $this->produkId]);

            return;
        }

        if ($produk->trashed()) {
            $this->handleSoftDeleted($produk);

            return;
        }

        $quantity = $this->calculateStockQuantity($produk);

        try {
            $wooService = new WooCommerceService;
            $result = $wooService->updateProductStockBySku($produk->sku, $quantity);

            Log::info('SyncStockToWooCommerce: Stock synced', [
                'produk_id' => $this->produkId,
                'sku' => $produk->sku,
                'quantity' => $quantity,
                'woo_response' => $result,
            ]);
        } catch (RequestException $e) {
            Log::error('SyncStockToWooCommerce: Failed to sync', [
                'produk_id' => $this->produkId,
                'sku' => $produk->sku,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function calculateStockQuantity(Produk $produk): int
    {
        return (int) $produk->pembelianItems()
            ->selectRaw('COALESCE(SUM(qty_sisa), 0) as total_stock')
            ->value('total_stock');
    }

    protected function handleSoftDeleted(Produk $produk): void
    {
        try {
            $wooService = new WooCommerceService;
            $product = $wooService->getProductBySku($produk->sku);

            if ($product) {
                $wooService->updateProduct($product['id'], ['status' => 'draft']);

                Log::info('SyncStockToWooCommerce: Product set to draft (soft-deleted)', [
                    'produk_id' => $this->produkId,
                    'sku' => $produk->sku,
                    'woo_product_id' => $product['id'],
                ]);
            }
        } catch (RequestException $e) {
            Log::error('SyncStockToWooCommerce: Failed to set product to draft', [
                'produk_id' => $this->produkId,
                'sku' => $produk->sku,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SyncStockToWooCommerce: Job failed permanently', [
            'produk_id' => $this->produkId,
            'error' => $exception->getMessage(),
        ]);
    }
}
