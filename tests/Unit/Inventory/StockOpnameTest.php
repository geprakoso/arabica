<?php

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Models\StockBatch;
use App\Models\StockMutation;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable WooCommerce sync job during tests
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
// STOCK OPNAME CREATION
// ============================================================

describe('StockOpname Creation', function () {
    test('bisa membuat stock opname draft', function () {
        $opname = StockOpname::create([
            'tanggal' => now(),
            'user_id' => $this->user->id,
        ]);

        expect($opname->status)->toBe('draft');
        expect($opname->kode)->toStartWith('SO-');
        expect($opname->isPosted())->toBeFalse();
    });

    test('kode opname unique dan auto-increment', function () {
        $opname1 = StockOpname::create(['tanggal' => now()]);
        $opname2 = StockOpname::create(['tanggal' => now()]);

        expect($opname1->kode)->not->toBe($opname2->kode);
    });
});

// ============================================================
// STOCK OPNAME ITEM
// ============================================================

describe('StockOpnameItem Selisih', function () {
    test('selisih dihitung otomatis (fisik - sistem)', function () {
        $opname = StockOpname::create(['tanggal' => now()]);

        $item = StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 10,
            'stok_fisik' => 8,
        ]);

        expect($item->selisih)->toBe(-2);
    });

    test('selisih positif jika fisik > sistem', function () {
        $opname = StockOpname::create(['tanggal' => now()]);

        $item = StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 5,
            'stok_fisik' => 12,
        ]);

        expect($item->selisih)->toBe(7);
    });

    test('selisih nol jika fisik = sistem', function () {
        $opname = StockOpname::create(['tanggal' => now()]);

        $item = StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 10,
            'stok_fisik' => 10,
        ]);

        expect($item->selisih)->toBe(0);
    });
});

// ============================================================
// STOCK OPNAME POSTING - ATOMIC
// ============================================================

describe('StockOpname Posting', function () {
    test('posting mengupdate stok batch dengan selisih', function () {
        // Setup: Create pembelian with stock
        $pembelian = Pembelian::create([
            'no_po' => 'PO-OPNAME-001',
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

        // Create opname with selisih -2 (stok fisik 8)
        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'stok_sistem' => 10,
            'stok_fisik' => 8,
            'selisih' => -2,
        ]);

        // Posting
        $result = $opname->post($this->user);
        expect($result)->toBeTrue();

        // Check batch updated
        $batch->refresh();
        expect($batch->qty_available)->toBe(8);

        // Check mutation log
        $mutation = StockMutation::where('type', 'opname')->first();
        expect($mutation)->not->toBeNull();
        expect($mutation->qty_change)->toBe(-2);
        expect($mutation->qty_before)->toBe(10);
        expect($mutation->qty_after)->toBe(8);

        // Check opname status
        $opname->refresh();
        expect($opname->isPosted())->toBeTrue();
        expect($opname->posted_by_id)->toBe($this->user->id);
    });

    test('posting mengupdate stok batch dengan selisih positif', function () {
        // Setup
        $pembelian = Pembelian::create([
            'no_po' => 'PO-OPNAME-002',
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

        // Create opname with selisih +3 (stok fisik 13)
        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'stok_sistem' => 10,
            'stok_fisik' => 13,
            'selisih' => 3,
        ]);

        $opname->post($this->user);

        $batch->refresh();
        expect($batch->qty_available)->toBe(13);
    });

    test('posting skip item dengan selisih 0', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-OPNAME-003',
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

        // Create opname with selisih 0
        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'stok_sistem' => 10,
            'stok_fisik' => 10,
            'selisih' => 0,
        ]);

        $opname->post($this->user);

        // Stok tidak berubah
        $batch->refresh();
        expect($batch->qty_available)->toBe(10);

        // Tidak ada mutation log untuk selisih 0
        expect(StockMutation::count())->toBe(0);
    });

    test('posting gagal jika opname sudah diposting', function () {
        $opname = StockOpname::create([
            'tanggal' => now(),
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('sudah diposting');

        $opname->post($this->user);
    });

    test('posting gagal jika tidak ada items', function () {
        $opname = StockOpname::create(['tanggal' => now()]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('minimal 1 item');

        $opname->post($this->user);
    });
});

// ============================================================
// STOCK OPNAME VALIDATION
// ============================================================

describe('StockOpname Validation', function () {
    test('posting gagal jika batch sedang RMA aktif', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-OPNAME-RMA',
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

        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'stok_sistem' => 10,
            'stok_fisik' => 8,
            'selisih' => -2,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('RMA aktif');

        $opname->post($this->user);
    });

    test('posting gagal jika selisih menyebabkan stok negatif', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-OPNAME-NEG',
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

        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $pembelianItem->id_pembelian_item,
            'stok_sistem' => 5,
            'stok_fisik' => 0, // Selisih -5, stok jadi 0
            'selisih' => -5,
        ]);

        // This should succeed (stok jadi 0, tidak negatif)
        $result = $opname->post($this->user);
        expect($result)->toBeTrue();

        $batch = $pembelianItem->stockBatch->fresh();
        expect($batch->qty_available)->toBe(0);
    });

    test('posting atomic - rollback jika salah satu item gagal', function () {
        // Setup batch 1
        $pembelian1 = Pembelian::create([
            'no_po' => 'PO-OPNAME-ATOMIC-1',
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

        // Setup batch 2 with active RMA (will fail)
        $pembelian2 = Pembelian::create([
            'no_po' => 'PO-OPNAME-ATOMIC-2',
            'tanggal' => now(),
            'id_supplier' => $this->supplier->id,
        ]);

        $item2 = PembelianItem::create([
            'id_pembelian' => $pembelian2->id_pembelian,
            'id_produk' => $this->produk->id,
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);

        // Create active RMA for item 2
        Rma::create([
            'id_pembelian_item' => $item2->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Create opname with 2 items
        $opname = StockOpname::create(['tanggal' => now()]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $item1->id_pembelian_item,
            'stok_sistem' => 10,
            'stok_fisik' => 8,
            'selisih' => -2,
        ]);
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'pembelian_item_id' => $item2->id_pembelian_item,
            'stok_sistem' => 5,
            'stok_fisik' => 3,
            'selisih' => -2,
        ]);

        // Should fail because item 2 has active RMA
        try {
            $opname->post($this->user);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            // Expected
        }

        // Verify item 1 was NOT updated (atomic rollback)
        $batch1 = $item1->stockBatch->fresh();
        expect($batch1->qty_available)->toBe(10); // Should still be 10, not 8

        // Verify opname is still draft
        $opname->refresh();
        expect($opname->isPosted())->toBeFalse();
    });
});

// ============================================================
// STOCK OPNAME SUMMARY
// ============================================================

describe('StockOpname Summary', function () {
    test('summary menampilkan informasi yang benar', function () {
        $opname = StockOpname::create(['tanggal' => now()]);

        // Item dengan selisih positif
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 10,
            'stok_fisik' => 12,
            'selisih' => 2,
        ]);

        // Item dengan selisih negatif
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 5,
            'stok_fisik' => 3,
            'selisih' => -2,
        ]);

        // Item tanpa selisih
        StockOpnameItem::create([
            'stock_opname_id' => $opname->id,
            'produk_id' => $this->produk->id,
            'stok_sistem' => 8,
            'stok_fisik' => 8,
            'selisih' => 0,
        ]);

        $summary = $opname->getSummary();

        expect((int) $summary['total_items'])->toBe(3);
        expect((int) $summary['total_selisih_positif'])->toBe(2);
        expect((int) $summary['total_selisih_negatif'])->toBe(2);
        expect((int) $summary['total_tanpa_selisih'])->toBe(1);
    });
});
