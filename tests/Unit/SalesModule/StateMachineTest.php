<?php

use App\Models\Member;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Produk;
use App\Models\StockBatch;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Support\Facades\Bus;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Bus::fake([\App\Jobs\SyncStockToWooCommerce::class]);

    // Create a user for auth
    $user = User::factory()->create();
    auth()->login($user);

    // Create supporting records
    $this->kategori = \App\Models\Kategori::create(['nama_kategori' => 'Test Kategori', 'slug' => 'test-kategori']);
    $this->brand = \App\Models\Brand::create(['nama_brand' => 'Test Brand', 'slug' => 'test-brand']);
    $this->supplier = \App\Models\Supplier::create([
        'nama_supplier' => 'Test Supplier',
        'no_hp' => '08123456789',
        'alamat' => 'Test Address',
    ]);
});

// ============================================================
// HELPERS
// ============================================================

function createProduk(array $attributes = []): Produk
{
    $test = test();
    return Produk::create(array_merge([
        'nama_produk' => 'Test Produk ' . uniqid(),
        'sku' => 'SKU-' . uniqid(),
        'harga_beli' => 50000,
        'harga_jual' => 75000,
        'stok' => 100,
        'kategori_id' => $test->kategori->id,
        'brand_id' => $test->brand->id,
    ], $attributes));
}

function createPembelianItem(int $qty = 10, array $attributes = []): PembelianItem
{
    $test = test();
    $produk = $attributes['produk'] ?? createProduk();

    $pembelian = Pembelian::create([
        'no_po' => 'PO-' . uniqid(),
        'tanggal' => now(),
        'tanggal_pembelian' => now(),
        'id_supplier' => $test->supplier->id,
    ]);

    $item = PembelianItem::create([
        'id_pembelian' => $pembelian->id_pembelian,
        'id_produk' => $produk->id,
        'qty' => $qty,
        'qty_masuk' => $qty,
        'qty_sisa' => $qty,
        'hpp' => $attributes['hpp'] ?? 50000,
        'harga_jual' => $attributes['harga_jual'] ?? 75000,
        'kondisi' => $attributes['kondisi'] ?? 'baru',
    ]);

    // StockBatch auto-created via PembelianItem observer
    $item->refresh();

    return $item;
}

function createStockBatch(int $qty = 10, array $attributes = []): StockBatch
{
    $item = createPembelianItem($qty, $attributes);
    return $item->stockBatch;
}

function createPenjualan(array $attributes = []): Penjualan
{
    $member = Member::create([
        'nama_member' => 'Test Member ' . uniqid(),
        'no_hp' => '0812' . rand(10000000, 99999999),
    ]);

    return Penjualan::create(array_merge([
        'id_member' => $member->id_member,
        'tanggal_penjualan' => now(),
        'status_dokumen' => $attributes['status_dokumen'] ?? 'draft',
        'is_locked' => $attributes['is_locked'] ?? false,
        'void_used' => $attributes['void_used'] ?? false,
    ], $attributes));
}

function createPenjualanItem(Penjualan $penjualan, StockBatch $batch, int $qty, array $attributes = []): PenjualanItem
{
    return PenjualanItem::create(array_merge([
        'id_penjualan' => $penjualan->id_penjualan,
        'id_produk' => $batch->pembelianItem->id_produk,
        'id_pembelian_item' => $batch->pembelian_item_id,
        'qty' => $qty,
        'harga_jual' => 100000,
        'kondisi' => 'baru',
    ], $attributes));
}

// ============================================================
// PHASE 1: STOCK BATCH INTEGRATION
// ============================================================

test('penjualan mengurangi stock_batch_qty_available', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan();
    createPenjualanItem($penjualan, $batch, 3);

    expect($batch->fresh()->qty_available)->toBe(7);
    expect(StockMutation::where('type', 'sale')->count())->toBe(1);
});

test('hapus penjualan_item mengembalikan stok ke batch', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batch, 3);

    $item->delete();

    expect($batch->fresh()->qty_available)->toBe(10);
    expect(StockMutation::where('type', 'sale_return')->count())->toBe(1);
});

test('update penjualan_item batch mengembalikan stok lama dan kurangi stok baru', function () {
    $batchA = createStockBatch(10);
    $batchB = createStockBatch(10);
    $penjualan = createPenjualan();
    $item = createPenjualanItem($penjualan, $batchA, 3);

    $item->update(['id_pembelian_item' => $batchB->pembelian_item_id, 'qty' => 2]);

    expect($batchA->fresh()->qty_available)->toBe(10);  // kembali
    expect($batchB->fresh()->qty_available)->toBe(8);   // terdeduct
});

test('penjualan melebihi stok gagal dengan exception', function () {
    $batch = createStockBatch(5);
    $penjualan = createPenjualan();

    expect(fn () => createPenjualanItem($penjualan, $batch, 10))
        ->toThrow(\Exception::class);
});

// ============================================================
// PHASE 2: STATE MACHINE
// ============================================================

test('penjualan baru status draft', function () {
    $penjualan = createPenjualan();
    expect($penjualan->isDraft())->toBeTrue();
    expect($penjualan->isFinal())->toBeFalse();
});

test('draft bisa di-post jadi final', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    $penjualan->post();

    expect($penjualan->fresh()->isFinal())->toBeTrue();
    expect($penjualan->fresh()->posted_at)->not->toBeNull();
});

test('final bisa di-void ke draft 1x', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);

    expect($penjualan->canVoid())->toBeTrue();

    $penjualan->voidToDraft();

    expect($penjualan->fresh()->isDraft())->toBeTrue();
    expect($penjualan->fresh()->void_used)->toBeTrue();
    expect($penjualan->fresh()->canVoid())->toBeFalse();
});

test('final bisa di-lock', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);

    expect($penjualan->canLock())->toBeTrue();

    $penjualan->lockFinal();

    expect($penjualan->fresh()->is_locked)->toBeTrue();
    expect($penjualan->fresh()->canLock())->toBeFalse();
});

test('draft baru bisa edit items dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);

    expect($penjualan->canEditItems())->toBeTrue();
    expect($penjualan->canEditJasa())->toBeTrue();
});

test('draft saved tidak bisa edit items dan jasa', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, createStockBatch(10), 2);
    $penjualan->refresh();

    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeTrue();
});

test('final tidak bisa edit apapun', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);

    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeFalse();
});

test('draft hasil void hanya bisa edit payment', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final']);
    createPenjualanItem($penjualan, createStockBatch(10), 2);
    $penjualan->voidToDraft();
    $penjualan->refresh();

    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeTrue();
});

test('lock final mencegah semua edit dan void', function () {
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => true]);

    expect($penjualan->canEditItems())->toBeFalse();
    expect($penjualan->canEditJasa())->toBeFalse();
    expect($penjualan->canEditPayment())->toBeFalse();
    expect($penjualan->canVoid())->toBeFalse();
});

// ============================================================
// DELETE & STOK
// ============================================================

test('final posted bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();

    expect($batch->fresh()->qty_available)->toBe(7);

    $penjualan->delete();

    expect($batch->fresh()->qty_available)->toBe(10);
    expect(StockMutation::where('reference_id', $penjualan->id_penjualan)->count())->toBe(0);
});

test('final locked bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'final', 'is_locked' => true]);
    createPenjualanItem($penjualan, $batch, 3);

    expect($batch->fresh()->qty_available)->toBe(7);

    $penjualan->delete();

    expect($batch->fresh()->qty_available)->toBe(10);
});

test('draft hasil void bisa dihapus dan stok dikembalikan', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();
    $penjualan->voidToDraft();

    expect($batch->fresh()->qty_available)->toBe(7); // stok tetap terdeduct

    $penjualan->delete();

    expect($batch->fresh()->qty_available)->toBe(10); // stok kembali!
});

test('stok tidak dikembalikan saat void', function () {
    $batch = createStockBatch(10);
    $penjualan = createPenjualan(['status_dokumen' => 'draft']);
    createPenjualanItem($penjualan, $batch, 3);
    $penjualan->post();

    expect($batch->fresh()->qty_available)->toBe(7);

    $penjualan->voidToDraft();

    expect($batch->fresh()->qty_available)->toBe(7); // TETAP!
});

test('tidak bisa hapus kalau ada tukar tambah', function () {
    $penjualan = createPenjualan(['sumber_transaksi' => 'tukar_tambah']);

    expect($penjualan->canDelete())->toBeFalse();

    expect(fn () => $penjualan->delete())
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

// ============================================================
// STATUS PEMBAYARAN
// ============================================================

test('status tempo ketika belum lunas', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create([
        'jumlah' => 500000,
        'tanggal' => now(),
        'metode_bayar' => 'cash',
    ]);

    expect($penjualan->status_pembayaran)->toBe('TEMPO');
});

test('status lunas ketika sudah lunas', function () {
    $penjualan = createPenjualan(['grand_total' => 1000000]);
    $penjualan->pembayaran()->create([
        'jumlah' => 1000000,
        'tanggal' => now(),
        'metode_bayar' => 'cash',
    ]);

    expect($penjualan->status_pembayaran)->toBe('LUNAS');
});
