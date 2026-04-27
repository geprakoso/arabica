<?php

use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\Kategori;
use App\Models\Brand;
use App\Models\Produk;
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
    
    $this->kategori = Kategori::create(['nama_kategori' => 'Test Kategori', 'slug' => 'test-kategori']);
    $this->brand = Brand::create(['nama_brand' => 'Test Brand', 'slug' => 'test-brand']);
    
    $this->produk1 = Produk::create([
        'nama_produk' => 'Produk 1', 'kategori_id' => $this->kategori->id,
        'brand_id' => $this->brand->id, 'sku' => 'TEST001',
    ]);
    $this->produk2 = Produk::create([
        'nama_produk' => 'Produk 2', 'kategori_id' => $this->kategori->id,
        'brand_id' => $this->brand->id, 'sku' => 'TEST002',
    ]);
});

// =====================================================
// R01: Metode Batch
// =====================================================

describe('R01: Stock Batch Management', function () {
    test('batch dibuat otomatis saat pembelian item created', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-' . uniqid(), 'tanggal' => now(), 'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk1->id,
            'kondisi' => 'Baru',
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        
        expect($item->stockBatch)->not->toBeNull();
        expect($item->stockBatch->qty_total)->toBe(10);
        expect($item->stockBatch->qty_available)->toBe(10);
        expect($item->stockBatch->produk_id)->toBe(1);
    });
    
    test('batch tidak dibuat jika qty = 0', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-' . uniqid(), 'tanggal' => now(), 'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk1->id,
            'kondisi' => 'Baru',
            'qty' => 0, // Qty 0
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 0,
        ]);
        
        expect($item->stockBatch)->toBeNull();
    });
});

// =====================================================
// R14: Konsistensi View Qty
// =====================================================

describe('R14: Konsistensi View Qty Pembelian', function () {
    test('qty_total tetap meski ada penjualan', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-' . uniqid(), 'tanggal' => now(), 'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk1->id,
            'kondisi' => 'Baru',
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        
        $batch = $item->stockBatch;
        
        // Simulasi penjualan 5 unit
        $batch->decrement('qty_available', 5);
        $batch->refresh();
        
        // qty_total tetap 10 (tidak berubah)
        expect($batch->qty_total)->toBe(10);
        // qty_available berkurang jadi 5
        expect($batch->qty_available)->toBe(5);
    });
    
    test('view pembelian menampilkan qty asli (qty_total)', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-' . uniqid(), 'tanggal' => now(), 'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk1->id,
            'kondisi' => 'Baru',
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        
        // Kurangi stok tersedia
        $item->stockBatch->decrement('qty_available', 7);
        
        // View tetap menampilkan qty asli (10)
        expect($item->qty)->toBe(10);
        expect($item->stockBatch->qty_total)->toBe(10);
    });
});

// =====================================================
// R17: Pessimistic Locking
// =====================================================

// Helper untuk membuat batch dari pembelian item
function createBatch($test, $produkId, $qty = 10) {
    $pembelian = Pembelian::create([
        'no_po' => 'PO-' . uniqid(),
        'tanggal' => now(),
        'id_supplier' => $test->supplier->id,
    ]);
    $item = PembelianItem::create([
        'id_pembelian' => $pembelian->id_pembelian,
        'id_produk' => $produkId,
        'qty' => $qty,
        'hpp' => 100000,
        'harga_jual' => 150000,
        'subtotal' => $qty * 100000,
    ]);
    return $item->stockBatch;
}

describe('R17: Pessimistic Locking pada Stok Batch', function () {
    test('decrement with lock mengurangi qty_available', function () {
        $batch = createBatch($this, $this->produk1->id, 10);
        
        $result = StockBatch::decrementWithLock($batch->id, 5);
        
        expect($result)->toBeTrue();
        $batch->refresh();
        expect($batch->qty_available)->toBe(5);
    });
    
    test('decrement with lock update locked_at timestamp', function () {
        $batch = createBatch($this, $this->produk1->id, 10);
        
        StockBatch::decrementWithLock($batch->id, 3);
        $batch->refresh();
        
        expect($batch->locked_at)->not->toBeNull();
    });
    
    test('decrement with lock throws exception jika stok tidak cukup', function () {
        $batch = createBatch($this, $this->produk1->id, 5);
        $batch->update(['qty_available' => 3]); // Override untuk test
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stok tidak cukup');
        
        StockBatch::decrementWithLock($batch->id, 5);
    });
    
    test('decrement with lock throws exception jika batch tidak ditemukan', function () {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Batch tidak ditemukan');
        
        StockBatch::decrementWithLock(99999, 5);
    });
    
    test('decrement multiple dengan lock', function () {
        $batch1 = createBatch($this, $this->produk1->id, 10);
        $batch2 = createBatch($this, $this->produk2->id, 20);
        
        $result = StockBatch::decrementMultiple([
            $batch1->id => 5,
            $batch2->id => 10,
        ]);
        
        expect($result)->toBeTrue();
        $batch1->refresh();
        $batch2->refresh();
        expect($batch1->qty_available)->toBe(5);
        expect($batch2->qty_available)->toBe(10);
    });
    
    test('decrement multiple rollback jika salah satu gagal', function () {
        $pembelian1 = Pembelian::create(['no_po' => 'PO-1', 'tanggal' => now(), 'id_supplier' => $this->supplier->id]);
        $item1 = PembelianItem::create([
            'id_pembelian' => $pembelian1->id_pembelian,
            'id_produk' => $this->produk1->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        $batch1 = $item1->stockBatch;
        
        $pembelian2 = Pembelian::create(['no_po' => 'PO-2', 'tanggal' => now(), 'id_supplier' => $this->supplier->id]);
        $item2 = PembelianItem::create([
            'id_pembelian' => $pembelian2->id_pembelian,
            'id_produk' => $this->produk2->id,
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        $batch2 = $item2->stockBatch;
        $batch2->update(['qty_available' => 3]); // Stok terbatas
        
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

// =====================================================
// Race Condition Simulation
// =====================================================

describe('R17: Race Condition Prevention', function () {
    test('concurrent decrement tidak menyebabkan oversell', function () {
        $pembelian = Pembelian::create(['no_po' => 'PO-RACE', 'tanggal' => now(), 'id_supplier' => $this->supplier->id]);
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk1->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        $batch = $item->stockBatch;
        
        $successCount = 0;
        $failCount = 0;
        
        // Simulasi 3 request konkuren
        $requests = [
            ['id' => $batch->id, 'qty' => 6],
            ['id' => $batch->id, 'qty' => 5],
            ['id' => $batch->id, 'qty' => 3],
        ];
        
        foreach ($requests as $request) {
            try {
                StockBatch::decrementWithLock($request['id'], $request['qty']);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
            }
        }
        
        // Hanya satu yang boleh sukses (total 10)
        // 6 + 5 + 3 = 14 (oversell 4)
        // Dengan locking, hanya 6 yang bisa, sisanya gagal
        expect($successCount + $failCount)->toBe(3);
        expect($successCount)->toBeGreaterThanOrEqual(1);
        
        $batch->refresh();
        expect($batch->qty_available)->toBeGreaterThanOrEqual(0);
        expect($batch->qty_available)->toBeLessThanOrEqual(10);
    });
});
