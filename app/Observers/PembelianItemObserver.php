<?php

namespace App\Observers;

use App\Jobs\SyncStockToWooCommerce;
use App\Models\PembelianItem;
use Illuminate\Support\Facades\Log;

class PembelianItemObserver
{
    public function created(PembelianItem $pembelianItem): void
    {
        $this->dispatchSyncIfNeeded($pembelianItem, 'created');
    }

    public function updated(PembelianItem $pembelianItem): void
    {
        if (! $pembelianItem->wasChanged('qty_sisa')) {
            return;
        }

        $this->dispatchSyncIfNeeded($pembelianItem, 'updated');
    }

    protected function dispatchSyncIfNeeded(PembelianItem $pembelianItem, string $event): void
    {
        $produk = $pembelianItem->produk;

        if (! $produk || blank($produk->sku)) {
            Log::debug('PembelianItemObserver: Skipping sync - no valid produk or SKU', [
                'pembelian_item_id' => $pembelianItem->getKey(),
                'event' => $event,
            ]);

            return;
        }

        SyncStockToWooCommerce::dispatch($produk->id);

        Log::info('PembelianItemObserver: SyncStockToWooCommerce dispatched', [
            'pembelian_item_id' => $pembelianItem->getKey(),
            'produk_id' => $produk->id,
            'sku' => $produk->sku,
            'qty_sisa' => $pembelianItem->qty_sisa,
            'event' => $event,
        ]);
    }
}
