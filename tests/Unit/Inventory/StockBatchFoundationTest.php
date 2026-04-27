<?php

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\StockBatch;
use App\Models\StockMutation;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Bus::fake([\App\Jobs\SyncStockToWooCommerce::class]);
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

// Helper function untuk create batch
function createTestBatch($produk, $supplier, $qty = 10, $available = null) {
    $available = $available ?? $qty;
    
    $pembelian = Pembelian::create([
        'no_po' => 'PO-' . uniqid(),
        'tanggal' => now(),
        'id_supplier' => $supplier->id,
    ]);

    $item = PembelianItem::create([
        'id_pembelian' => $pembelian->id_pembelian,
        'id_produk' => $produk->id,
        'kondisi' => 'Baru',
        'qty' => $qty,
        'hpp' => 100000,
        'harga_jual' => 150000,
        'subtotal' => $qty * 100000,
    ]);

    $batch = $item->stockBatch;
    if ($available !== $qty) {
        $batch->update(['qty_available' => $available]);
    }
    
    return $batch;
}

// ============================================================
// STOCK BATCH FOUNDATION TESTS
// ============================================================

describe('StockBatch Creation', function () {
    test('stock batch dibuat otomatis saat pembelian item created', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-001',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'kondisi' => 'Baru',
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        expect($item->stockBatch)->not->toBeNull();
        expect($item->stockBatch->qty_total)->toBe(10);
        expect($item->stockBatch->qty_available)->toBe(10);
        expect($item->stockBatch->produk_id)->toBe($this->produk->id);
    });

    test('stock batch tidak dibuat jika qty = 0', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-002',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'kondisi' => 'Baru',
            'qty' => 0,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 0,
        ]);

        expect($item->stockBatch)->toBeNull();
    });
});

describe('StockBatch incrementWithLock', function () {
    test('increment with lock menambah stok dan membuat audit trail', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        $result = StockBatch::incrementWithLock($batch->id, 5, [
            'type' => 'adjustment',
            'reference_type' => 'StockAdjustment',
            'reference_id' => 1,
            'notes' => 'Test adjustment',
        ]);

        expect($result)->toBeTrue();
        
        $batch->refresh();
        expect($batch->qty_available)->toBe(15);

        // Check audit trail
        $mutation = StockMutation::first();
        expect($mutation)->not->toBeNull();
        expect($mutation->qty_change)->toBe(5);
        expect($mutation->qty_before)->toBe(10);
        expect($mutation->qty_after)->toBe(15);
        expect($mutation->type)->toBe('adjustment');
    });

    test('increment with lock update locked_at timestamp', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        expect($batch->locked_at)->toBeNull();

        StockBatch::incrementWithLock($batch->id, 3);

        $batch->refresh();
        expect($batch->locked_at)->not->toBeNull();
    });

    test('increment with lock menggunakan database transaction', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        // Simulasi error dalam transaction
        try {
            DB::transaction(function () use ($batch) {
                StockBatch::incrementWithLock($batch->id, 5, [
                    'type' => 'adjustment',
                ]);
                
                // Force throw exception
                throw new \Exception('Simulated error');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $batch->refresh();
        // Stok seharusnya tidak berubah karena transaction rollback
        expect($batch->qty_available)->toBe(10);
    });
});

describe('StockBatch decrementWithLock', function () {
    test('decrement with lock mengurangi stok dan membuat audit trail', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        $result = StockBatch::decrementWithLock($batch->id, 3, [
            'type' => 'sale',
            'reference_type' => 'Penjualan',
            'reference_id' => 1,
        ]);

        expect($result)->toBeTrue();
        
        $batch->refresh();
        expect($batch->qty_available)->toBe(7);

        $mutation = StockMutation::first();
        expect($mutation->qty_change)->toBe(-3);
        expect($mutation->qty_before)->toBe(10);
        expect($mutation->qty_after)->toBe(7);
        expect($mutation->type)->toBe('sale');
    });

    test('decrement with lock throws exception jika stok tidak cukup', function () {
        $batch = createTestBatch($this->produk, $this->supplier, 5, 3);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stok tidak cukup');

        StockBatch::decrementWithLock($batch->id, 5);
    });

    test('decrement with lock throws exception jika batch tidak ditemukan', function () {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Batch tidak ditemukan');

        StockBatch::decrementWithLock(99999, 5);
    });
});

describe('StockBatch Multiple Operations', function () {
    test('increment multiple dengan lock', function () {
        $batch1 = createTestBatch($this->produk, $this->supplier, 10);
        $batch2 = createTestBatch($this->produk, $this->supplier, 20);

        $result = StockBatch::incrementMultiple([
            $batch1->id => 5,
            $batch2->id => 10,
        ], [
            'type' => 'adjustment',
            'reference_type' => 'StockAdjustment',
            'reference_id' => 1,
        ]);

        expect($result)->toBeTrue();

        $batch1->refresh();
        $batch2->refresh();

        expect($batch1->qty_available)->toBe(15);
        expect($batch2->qty_available)->toBe(30);

        expect(StockMutation::count())->toBe(2);
    });

    test('decrement multiple dengan lock', function () {
        $batch1 = createTestBatch($this->produk, $this->supplier, 10);
        $batch2 = createTestBatch($this->produk, $this->supplier, 20);

        $result = StockBatch::decrementMultiple([
            $batch1->id => 5,
            $batch2->id => 10,
        ], [
            'type' => 'sale',
        ]);

        expect($result)->toBeTrue();

        $batch1->refresh();
        $batch2->refresh();

        expect($batch1->qty_available)->toBe(5);
        expect($batch2->qty_available)->toBe(10);
    });

    test('decrement multiple rollback jika salah satu gagal', function () {
        $batch1 = createTestBatch($this->produk, $this->supplier, 10);
        $batch2 = createTestBatch($this->produk, $this->supplier, 5, 3); // Stok terbatas

        try {
            StockBatch::decrementMultiple([
                $batch1->id => 5,   // OK
                $batch2->id => 5,   // Gagal - stok tidak cukup
            ]);
        } catch (\Exception $e) {
            // Expected
        }

        // Batch 1 juga tidak berkurang karena transaction rollback
        $batch1->refresh();
        $batch2->refresh();
        expect($batch1->qty_available)->toBe(10); // Tidak berubah
        expect($batch2->qty_available)->toBe(3);  // Tidak berubah
    });
});

describe('StockBatch Scopes', function () {
    test('scope hasStock returns only batches with sufficient stock', function () {
        $batch1 = createTestBatch($this->produk, $this->supplier, 10);
        $batch2 = createTestBatch($this->produk, $this->supplier, 5, 0);

        $batches = StockBatch::hasStock(1)->get();

        expect($batches)->toHaveCount(1);
        expect($batches->first()->id)->toBe($batch1->id);
    });

    test('scope available returns batches with stock', function () {
        $batch = createTestBatch($this->produk, $this->supplier, 10);

        $batches = StockBatch::hasStock()->get();

        expect($batches)->toHaveCount(1);
    });
});

describe('StockMutation Audit Trail', function () {
    test('audit trail tersimpan dengan benar', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        StockBatch::incrementWithLock($batch->id, 5, [
            'type' => 'opname',
            'reference_type' => 'StockOpname',
            'reference_id' => 1,
            'notes' => 'Selisih stock opname',
        ]);

        $mutation = StockMutation::first();
        
        expect($mutation->stock_batch_id)->toBe($batch->id);
        expect($mutation->type)->toBe('opname');
        expect($mutation->reference_type)->toBe('StockOpname');
        expect($mutation->reference_id)->toBe(1);
        expect($mutation->notes)->toBe('Selisih stock opname');
    });

    test('bisa query mutations by type', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        // Create multiple mutations
        StockBatch::incrementWithLock($batch->id, 5, ['type' => 'adjustment']);
        StockBatch::decrementWithLock($batch->id, 3, ['type' => 'sale']);

        $adjustments = StockMutation::ofType('adjustment')->get();
        $sales = StockMutation::ofType('sale')->get();

        expect($adjustments)->toHaveCount(1);
        expect($sales)->toHaveCount(1);
    });

    test('bisa query incoming dan outgoing mutations', function () {
        $batch = createTestBatch($this->produk, $this->supplier);

        StockBatch::incrementWithLock($batch->id, 5, ['type' => 'adjustment']);
        StockBatch::decrementWithLock($batch->id, 3, ['type' => 'sale']);

        $incoming = StockMutation::incoming()->get();
        $outgoing = StockMutation::outgoing()->get();

        expect($incoming)->toHaveCount(1);
        expect($outgoing)->toHaveCount(1);
    });
});
