<?php

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Models\StockBatch;
use App\Models\StockMutation;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Supplier;
use App\Models\User;
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

    $this->user = User::factory()->create();
});

// ============================================================
// STOCK ADJUSTMENT CREATION
// ============================================================

describe('StockAdjustment Creation', function () {
    test('bisa membuat stock adjustment draft', function () {
        $adjustment = StockAdjustment::create([
            'tanggal' => now(),
            'user_id' => $this->user->id,
        ]);

        expect($adjustment->status)->toBe('draft');
        expect($adjustment->kode)->toStartWith('SA-');
        expect($adjustment->isPosted())->toBeFalse();
    });

    test('kode adjustment unique dan auto-increment', function () {
        $adjustment1 = StockAdjustment::create(['tanggal' => now()]);
        $adjustment2 = StockAdjustment::create(['tanggal' => now()]);

        expect($adjustment1->kode)->not->toBe($adjustment2->kode);
    });
});

// ============================================================
// STOCK ADJUSTMENT POSTING - ATOMIC
// ============================================================

describe('StockAdjustment Posting', function () {
    test('posting menambah stok batch dengan qty positif', function () {
        // Setup: Create pembelian with stock
        $pembelian = Pembelian::create([
            'no_po' => 'PO-ADJ-001',
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

        $batch = $pembelianItem->stockBatch;
        expect($batch->qty_available)->toBe(10);

        // Create adjustment with +5 qty
        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'qty' => 5,
            'keterangan' => 'Tambah stok rusak',
        ]);

        // Posting
        $result = $adjustment->post($this->user);
        expect($result)->toBeTrue();

        // Check batch updated
        $batch->refresh();
        expect($batch->qty_available)->toBe(15);

        // Check mutation log
        $mutation = StockMutation::where('type', 'adjustment')->first();
        expect($mutation)->not->toBeNull();
        expect($mutation->qty_change)->toBe(5);
        expect($mutation->qty_before)->toBe(10);
        expect($mutation->qty_after)->toBe(15);

        // Check adjustment status
        $adjustment->refresh();
        expect($adjustment->isPosted())->toBeTrue();
        expect($adjustment->posted_by_id)->toBe($this->user->id);
    });

    test('posting mengurangi stok batch dengan qty negatif', function () {
        // Setup
        $pembelian = Pembelian::create([
            'no_po' => 'PO-ADJ-002',
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

        $batch = $pembelianItem->stockBatch;

        // Create adjustment with -3 qty
        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'qty' => -3,
            'keterangan' => 'Stok rusak',
        ]);

        $adjustment->post($this->user);

        $batch->refresh();
        expect($batch->qty_available)->toBe(7);

        $mutation = StockMutation::where('type', 'adjustment')->first();
        expect($mutation->qty_change)->toBe(-3);
    });

    test('posting skip item dengan qty 0', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-ADJ-003',
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

        $batch = $pembelianItem->stockBatch;

        // Create adjustment with qty 0
        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'qty' => 0,
            'keterangan' => 'No change',
        ]);

        $adjustment->post($this->user);

        // Stok tidak berubah
        $batch->refresh();
        expect($batch->qty_available)->toBe(10);

        // Tidak ada mutation log untuk qty 0
        expect(StockMutation::count())->toBe(0);
    });

    test('posting gagal jika adjustment sudah diposting', function () {
        $adjustment = StockAdjustment::create([
            'tanggal' => now(),
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('sudah diposting');

        $adjustment->post($this->user);
    });

    test('posting gagal jika tidak ada items', function () {
        $adjustment = StockAdjustment::create(['tanggal' => now()]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('minimal 1 item');

        $adjustment->post($this->user);
    });
});

// ============================================================
// STOCK ADJUSTMENT VALIDATION
// ============================================================

describe('StockAdjustment Validation', function () {
    test('posting gagal jika batch sedang RMA aktif', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-ADJ-RMA',
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

        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'qty' => -3,
            'keterangan' => 'Test RMA',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('RMA aktif');

        $adjustment->post($this->user);
    });

    test('posting gagal jika adjustment menyebabkan stok negatif', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-ADJ-NEG',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $pembelianItem = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);

        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'qty' => -10, // Stok jadi -5, ini harus gagal
            'keterangan' => 'Test negatif',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('negatif');

        $adjustment->post($this->user);
    });

    test('posting atomic - rollback jika salah satu item gagal', function () {
        // Setup batch 1
        $pembelian1 = Pembelian::create([
            'no_po' => 'PO-ADJ-ATOMIC-1',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $item1 = PembelianItem::create([
            'id_pembelian' => $pembelian1->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);

        // Setup batch 2 (will fail with negative stock)
        $pembelian2 = Pembelian::create([
            'no_po' => 'PO-ADJ-ATOMIC-2',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $item2 = PembelianItem::create([
            'id_pembelian' => $pembelian2->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 2,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 200000,
        ]);

        // Create adjustment with 2 items
        $adjustment = StockAdjustment::create(['tanggal' => now()]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $item1->id_pembelian_item,
            'qty' => -5, // OK: 10 - 5 = 5
            'keterangan' => 'OK item',
        ]);
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $item2->id_pembelian_item,
            'qty' => -5, // FAIL: 2 - 5 = -3 (negative!)
            'keterangan' => 'Fail item',
        ]);

        // Should fail because item 2 would go negative
        try {
            $adjustment->post($this->user);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            // Expected
        }

        // Verify item 1 was NOT updated (atomic rollback)
        $batch1 = $item1->stockBatch->fresh();
        expect($batch1->qty_available)->toBe(10); // Should still be 10, not 5

        // Verify adjustment is still draft
        $adjustment->refresh();
        expect($adjustment->isPosted())->toBeFalse();
    });
});

// ============================================================
// STOCK ADJUSTMENT SUMMARY
// ============================================================

describe('StockAdjustment Summary', function () {
    test('summary menampilkan informasi yang benar', function () {
        $adjustment = StockAdjustment::create(['tanggal' => now()]);

        // Item penambahan
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'qty' => 5,
            'keterangan' => 'Tambah',
        ]);

        // Item pengurangan
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'qty' => -3,
            'keterangan' => 'Kurang',
        ]);

        // Item tanpa perubahan
        StockAdjustmentItem::create([
            'stock_adjustment_id' => $adjustment->id,
            'produk_id' => $this->produk->id,
            'qty' => 0,
            'keterangan' => 'No change',
        ]);

        $summary = $adjustment->getSummary();

        expect((int) $summary['total_items'])->toBe(3);
        expect((int) $summary['total_penambahan'])->toBe(5);
        expect((int) $summary['total_pengurangan'])->toBe(3);
        expect((int) $summary['total_tanpa_perubahan'])->toBe(1);
    });
});
