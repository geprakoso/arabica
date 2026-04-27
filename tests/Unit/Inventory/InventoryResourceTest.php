<?php

use App\Filament\Resources\InventoryResource;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Models\StockBatch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SyncStockToWooCommerce::class]);

    $this->supplier = Supplier::create([
        'nama_supplier' => 'Test Supplier',
        'no_hp' => '08123456789',
        'alamat' => 'Test Address',
    ]);

    $this->kategori = Kategori::create([
        'nama_kategori' => 'Test Kategori',
        'slug' => 'test-kategori',
    ]);

    $this->brand = Brand::create([
        'nama_brand' => 'Test Brand',
        'slug' => 'test-brand',
    ]);

    $this->produk = Produk::create([
        'nama_produk' => 'Test Produk',
        'kategori_id' => $this->kategori->id,
        'brand_id' => $this->brand->id,
        'sku' => 'TEST001',
    ]);
});

// ============================================================
// INVENTORY SNAPSHOT
// ============================================================

describe('Inventory Snapshot', function () {
    test('getInventorySnapshot returns correct qty from StockBatch', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-001',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $pembelianItem = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        $snapshot = InventoryResource::getInventorySnapshot($this->produk);

        expect($snapshot['qty'])->toBe(10);
        expect($snapshot['batch_count'])->toBe(1);
        expect($snapshot['batches'])->toHaveCount(1);
        expect($snapshot['latest_batch']['hpp'])->toBe(100000);
        expect($snapshot['latest_batch']['harga_jual'])->toBe(150000);
    });

    test('getInventorySnapshot uses StockBatch as primary source', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-002',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $pembelianItem = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Modify StockBatch directly
        $batch = StockBatch::where('pembelian_item_id', $pembelianItem->id_pembelian_item)->first();
        $batch->update(['qty_available' => 7]);

        $snapshot = InventoryResource::getInventorySnapshot($this->produk);

        // Should use StockBatch qty (7), not PembelianItem qty_sisa (10)
        expect($snapshot['qty'])->toBe(7);
    });

    test('getInventorySnapshot falls back to PembelianItem when no StockBatch', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-003',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $pembelianItem = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Delete StockBatch to force fallback
        StockBatch::where('pembelian_item_id', $pembelianItem->id_pembelian_item)->delete();

        $snapshot = InventoryResource::getInventorySnapshot($this->produk);

        expect($snapshot['qty'])->toBe(10);
        expect($snapshot['batch_count'])->toBe(1);
    });

    test('getInventorySnapshot excludes RMA active batches', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-RMA',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $pembelianItem = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Create active RMA
        Rma::create([
            'id_pembelian_item' => $pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        $snapshot = InventoryResource::getInventorySnapshot($this->produk);

        expect($snapshot['qty'])->toBe(0);
        expect($snapshot['batch_count'])->toBe(0);
    });

    test('snapshot cache prevents redundant queries', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-CACHE',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Call multiple times
        $snapshot1 = InventoryResource::getInventorySnapshot($this->produk);
        $snapshot2 = InventoryResource::getInventorySnapshot($this->produk);

        // Should be same object (cached)
        expect($snapshot1)->toBe($snapshot2);
    });
});

// ============================================================
// INVENTORY QUERY SCOPES
// ============================================================

describe('Inventory Query Scopes', function () {
    test('getEloquentQuery includes products with stock via StockBatch', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-SCOPE',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        $results = InventoryResource::getEloquentQuery()->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->id)->toBe($this->produk->id);
    });

    test('getEloquentQuery excludes products with zero stock', function () {
        $produkNoStock = Produk::create([
            'nama_produk' => 'No Stock Produk',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'NOSTOCK001',
        ]);

        $results = InventoryResource::getEloquentQuery()->get();

        expect($results->contains('id', $produkNoStock->id))->toBeFalse();
    });
});

// ============================================================
// FORMAT HELPERS
// ============================================================

describe('Format Helpers', function () {
    test('formatNumber formats correctly', function () {
        // Use reflection to test protected method
        $method = new ReflectionMethod(InventoryResource::class, 'formatNumber');
        $formatted = $method->invoke(null, 1234567);
        expect($formatted)->toBe('1.234.567');
    });

    test('formatCurrency formats IDR correctly', function () {
        $method = new ReflectionMethod(InventoryResource::class, 'formatCurrency');
        $formatted = $method->invoke(null, 150000);
        expect($formatted)->toContain('150.000');
    });
});

// ============================================================
// EXPORT SUMMARY
// ============================================================

describe('Export Summary', function () {
    test('buildExportSummary calculates totals correctly', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-INV-EXP',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Pre-compute snapshot
        InventoryResource::getInventorySnapshot($this->produk);

        // Set total_qty manually for the summary
        $this->produk->total_qty = 10;

        $records = collect([$this->produk]);
        $summary = InventoryResource::buildExportSummary($records);

        expect($summary[0]['label'])->toBe('Total Produk');
        expect($summary[0]['value'])->toBe('1');
        expect($summary[1]['label'])->toBe('Total Stok Sistem');
        expect($summary[1]['value'])->toBe('10');
        expect($summary[2]['label'])->toBe('Estimasi Nilai HPP');
        expect($summary[2]['value'])->toContain('1.000.000');
        expect($summary[3]['label'])->toBe('Estimasi Nilai Jual');
        expect($summary[3]['value'])->toContain('1.500.000');
    });
});
