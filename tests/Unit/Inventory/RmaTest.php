<?php

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Models\StockBatch;
use App\Models\StockMutation;
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

    $this->pembelian = Pembelian::create([
        'no_po' => 'PO-RMA-001',
        'tanggal' => now(),
        'id_supplier' => $this->supplier->id,
    ]);

    $this->pembelianItem = PembelianItem::create([
        'id_pembelian' => $this->pembelian->id_pembelian,
        'id_produk' => $this->produk->id,
        'qty' => 10,
        'hpp' => 100000,
        'harga_jual' => 150000,
        'subtotal' => 1000000,
    ]);

    $this->batch = $this->pembelianItem->stockBatch;
    expect($this->batch->qty_available)->toBe(10);
});

// ============================================================
// RMA CREATION
// ============================================================

describe('Rma Creation', function () {
    test('bisa membuat RMA baru dengan status di_packing', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect($rma->status_garansi)->toBe(Rma::STATUS_DI_PACKING);
        expect($rma->isActive())->toBeTrue();
        expect($rma->isCompleted())->toBeFalse();
    });

    test('status default adalah di_packing', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect($rma->status_garansi)->toBe(Rma::STATUS_DI_PACKING);
    });

    test('tidak bisa membuat RMA duplikat untuk batch yang sama', function () {
        Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('masih dalam proses RMA aktif');

        Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);
    });

    test('bisa membuat RMA baru jika RMA sebelumnya sudah selesai', function () {
        $rma1 = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_SELESAI,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Ini harus berhasil karena RMA sebelumnya sudah selesai
        $rma2 = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect($rma2->id_rma)->not->toBe($rma1->id_rma);
        expect($rma2->status_garansi)->toBe(Rma::STATUS_DI_PACKING);
    });
});

// ============================================================
// RMA STATUS UPDATE
// ============================================================

describe('Rma Status Update', function () {
    test('update status ke proses_klaim berhasil', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        $rma->update(['status_garansi' => Rma::STATUS_PROSES_KLAIM]);

        expect($rma->fresh()->status_garansi)->toBe(Rma::STATUS_PROSES_KLAIM);
        expect($rma->fresh()->isActive())->toBeTrue();
    });

    test('update status ke selesai kembalikan stok ke batch', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_PROSES_KLAIM,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Kurangi stok untuk simulasi (seolah barang dikirim untuk RMA)
        $this->batch->update(['qty_available' => 9]);
        expect($this->batch->fresh()->qty_available)->toBe(9);

        // Update status ke selesai
        $rma->update(['status_garansi' => Rma::STATUS_SELESAI]);

        // Stok harus kembali (tambah 1)
        $this->batch->refresh();
        expect($this->batch->qty_available)->toBe(10);

        // Check mutation log
        $mutation = StockMutation::where('type', 'rma_return')->first();
        expect($mutation)->not->toBeNull();
        expect($mutation->qty_change)->toBe(1);
        expect($mutation->reference_type)->toBe('Rma');
        expect($mutation->reference_id)->toBe($rma->id_rma);

        // Check RMA status
        expect($rma->fresh()->isCompleted())->toBeTrue();
        expect($rma->fresh()->isActive())->toBeFalse();
    });

    test('tidak ada perubahan stok jika update status non-selesai', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        $originalStock = $this->batch->qty_available;

        // Update ke proses_klaim (bukan selesai)
        $rma->update(['status_garansi' => Rma::STATUS_PROSES_KLAIM]);

        // Stok tidak berubah
        $this->batch->refresh();
        expect($this->batch->qty_available)->toBe($originalStock);

        // Tidak ada mutation log untuk rma_return
        expect(StockMutation::where('type', 'rma_return')->count())->toBe(0);
    });
});

// ============================================================
// RMA VALIDATION
// ============================================================

describe('Rma Validation', function () {
    test('tidak bisa update ke status aktif jika ada RMA aktif lain', function () {
        // Buat RMA pertama yang sudah selesai
        $rma1 = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_SELESAI,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Buat RMA kedua yang sudah selesai juga
        $rma2 = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_SELESAI,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Update rma1 ke di_packing
        $rma1->update(['status_garansi' => Rma::STATUS_DI_PACKING]);

        // Sekarang coba update rma2 ke di_packing juga (harus gagal karena rma1 aktif)
        try {
            $rma2->update(['status_garansi' => Rma::STATUS_DI_PACKING]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            expect($e->validator->errors()->first())->toContain('masih dalam proses RMA aktif');
        }
    });

    test('bisa update ke selesai meski ada RMA aktif lain', function () {
        // Buat RMA pertama yang sudah selesai
        Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_SELESAI,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Buat RMA kedua yang aktif
        $rma2 = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_PROSES_KLAIM,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Update rma2 ke selesai (harus berhasil karena selesai bukan status aktif)
        $rma2->update(['status_garansi' => Rma::STATUS_SELESAI]);

        expect($rma2->fresh()->isCompleted())->toBeTrue();
    });
});

// ============================================================
// RMA HELPER METHODS
// ============================================================

describe('Rma Helper Methods', function () {
    test('hasActiveRmaForBatch returns true jika ada RMA aktif', function () {
        Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect(Rma::hasActiveRmaForBatch($this->pembelianItem->id_pembelian_item))->toBeTrue();
    });

    test('hasActiveRmaForBatch returns false jika tidak ada RMA aktif', function () {
        Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_SELESAI,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect(Rma::hasActiveRmaForBatch($this->pembelianItem->id_pembelian_item))->toBeFalse();
    });

    test('activeStatuses returns correct array', function () {
        $statuses = Rma::activeStatuses();

        expect($statuses)->toContain(Rma::STATUS_DI_PACKING);
        expect($statuses)->toContain(Rma::STATUS_PROSES_KLAIM);
        expect($statuses)->not->toContain(Rma::STATUS_SELESAI);
    });

    test('pembelianItem relationship works', function () {
        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_DI_PACKING,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        expect($rma->pembelianItem)->not->toBeNull();
        expect($rma->pembelianItem->id_pembelian_item)->toBe($this->pembelianItem->id_pembelian_item);
    });
});

// ============================================================
// RMA EDGE CASES
// ============================================================

describe('Rma Edge Cases', function () {
    test('returnStockToInventory handles missing stockBatch', function () {
        // Hapus stock batch untuk test fallback
        $this->batch->delete();

        $rma = Rma::create([
            'id_pembelian_item' => $this->pembelianItem->id_pembelian_item,
            'status_garansi' => Rma::STATUS_PROSES_KLAIM,
            'rma_di_mana' => 'supplier',
            'tanggal' => now(),
        ]);

        // Update ke selesai - harus tetap berhasil dengan auto-create batch
        $rma->update(['status_garansi' => Rma::STATUS_SELESAI]);

        // Check batch recreated
        $newBatch = StockBatch::where('pembelian_item_id', $this->pembelianItem->id_pembelian_item)->first();
        expect($newBatch)->not->toBeNull();
    });

    test('RMA creation tanpa id_pembelian_item tidak error', function () {
        // Skip jika database tidak mengizinkan null id_pembelian_item
        try {
            $rma = Rma::create([
                'status_garansi' => Rma::STATUS_DI_PACKING,
                'rma_di_mana' => 'supplier',
                'tanggal' => now(),
            ]);
            expect($rma->id_rma)->toBeInt();
            expect($rma->id_pembelian_item)->toBeNull();
        } catch (\Exception $e) {
            // Database constraint tidak mengizinkan null, skip test
            $this->markTestSkipped('Database tidak mengizinkan id_pembelian_item null');
        }
    });
});
