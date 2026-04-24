<?php

use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\PembelianJasa;
use App\Models\PenjualanItem;
use App\Models\Produk;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    // Create supplier directly (no factory)
    $this->supplier = Supplier::create([
        'nama_supplier' => 'Test Supplier',
        'no_hp' => '08123456789',
        'alamat' => 'Test Address',
    ]);
});

// =====================================================
// R01-R05: SISTEM & METODE PEMBELIAN
// =====================================================

describe('R01: Metode Sistem Batch', function () {
    test('stock batch dibuat otomatis saat item pembelian dibuat', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 10,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 1000000,
        ]);
        
        expect($item->stockBatch)->not->toBeNull();
        expect($item->stockBatch->qty_total)->toBe(10);
        expect($item->stockBatch->qty_available)->toBe(10);
    });
});

describe('R02: Produk Duplikat dengan Kondisi Berbeda', function () {
    test('bisa menambah produk sama dengan kondisi berbeda', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        // Item 1: Baru
        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        
        // Item 2: Bekas (produk sama, kondisi berbeda) - HARUS BERHASIL
        $item2 = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Bekas',
            'qty' => 3,
            'hpp' => 80000,
            'harga_jual' => 120000,
            'subtotal' => 240000,
        ]);
        
        expect($item2)->not->toBeNull();
        expect($pembelian->items()->count())->toBe(2);
    });
    
    test('tidak bisa duplikat produk dengan kondisi sama', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        
        // Duplikat - HARUS GAGAL
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        
        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru', // Sama!
            'qty' => 3,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 300000,
        ]);
    });
});

describe('R03: Kolom Item Barang', function () {
    test('subtotal dihitung otomatis (qty × hpp)', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
        ]);
        
        expect($item->subtotal)->toBe(500000); // 5 × 100000
    });
});

describe('R04: Subtotal Menggantikan SN & Garansi', function () {
    test('kolom serials tidak ada di database', function () {
        $hasColumn = \Schema::hasColumn('tb_pembelian_item', 'serials');
        expect($hasColumn)->toBeFalse();
    });
    
    test('kolom subtotal ada di database', function () {
        $hasColumn = \Schema::hasColumn('tb_pembelian_item', 'subtotal');
        expect($hasColumn)->toBeTrue();
    });
});

describe('R05: Pembelian Item Jasa Tanpa Item Produk', function () {
    test('bisa buat pembelian hanya dengan item jasa', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        // Hanya jasa, tanpa item produk
        PembelianJasa::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'jasa_id' => 1,
            'qty' => 2,
            'harga' => 500000,
        ]);
        
        expect($pembelian->jasaItems()->count())->toBe(1);
        expect($pembelian->items()->count())->toBe(0);
    });
    
    test('bisa buat pembelian hanya dengan item produk', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        
        expect($pembelian->items()->count())->toBe(1);
        expect($pembelian->jasaItems()->count())->toBe(0);
    });
});

// =====================================================
// R06-R08: STATUS PEMBAYARAN
// =====================================================

describe('R06-R07: Status Pembayaran', function () {
    test('status TEMPO ketika pembayaran < grand_total', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 1000000,
            'total_paid' => 500000,
        ]);
        
        expect($pembelian->status)->toBe('TEMPO');
    });
    
    test('status LUNAS ketika pembayaran >= grand_total', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 1000000,
            'total_paid' => 1000000,
        ]);
        
        expect($pembelian->status)->toBe('LUNAS');
    });
    
    test('status LUNAS ketika kelebihan pembayaran', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 1000000,
            'total_paid' => 1200000,
        ]);
        
        expect($pembelian->status)->toBe('LUNAS');
    });
});

describe('R08: Kelebihan Pembayaran', function () {
    test('kelebihan dihitung dengan benar', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 1000000,
            'total_paid' => 1200000,
        ]);
        
        expect($pembelian->kelebihan)->toBe(200000);
    });
    
    test('kelebihan 0 ketika tidak ada kelebihan', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 1000000,
            'total_paid' => 800000,
        ]);
        
        expect($pembelian->kelebihan)->toBe(0);
    });
});

// =====================================================
// R11: Simpan Grand Total
// =====================================================

describe('R11: Simpan Grand Total di Database', function () {
    test('grand_total tersimpan di database', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'grand_total' => 2500000,
        ]);
        
        $this->assertDatabaseHas('tb_pembelian', [
            'id_pembelian' => $pembelian->id_pembelian,
            'grand_total' => 2500000,
        ]);
    });
});

// =====================================================
// R12-R13: VALIDASI HAPUS
// =====================================================

describe('R12: Cegah Hapus jika Ada Transaksi Penjualan', function () {
    test('tidak bisa hapus jika produk sudah terjual', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        $item = PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        
        // Simulasi produk sudah terjual
        PenjualanItem::create([
            'id_pembelian_item' => $item->id_pembelian_item,
            'id_produk' => 1,
            'qty' => 2,
            'harga_jual' => 150000,
        ]);
        
        expect($pembelian->canDelete())->toBeFalse();
    });
    
    test('bisa hapus jika belum ada penjualan', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
        ]);
        
        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => 1,
            'kondisi' => 'Baru',
            'qty' => 5,
            'hpp' => 100000,
            'harga_jual' => 150000,
            'subtotal' => 500000,
        ]);
        
        expect($pembelian->canDelete())->toBeTrue();
    });
});

describe('R13: Larangan Hapus NO PO dengan NO TT', function () {
    test('tidak bisa hapus jika memiliki NO TT', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'no_tt' => 'TT-001',
        ]);
        
        expect($pembelian->canDelete())->toBeFalse();
    });
    
    test('bisa hapus jika tidak memiliki NO TT', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'no_tt' => null,
        ]);
        
        expect($pembelian->canDelete())->toBeTrue();
    });
});

// =====================================================
// R16: LOCK FINAL
// =====================================================

describe('R16: Tombol Lock Final', function () {
    test('lock final set is_locked jadi true', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'is_locked' => false,
        ]);
        
        $pembelian->lockFinal();
        
        expect($pembelian->fresh()->is_locked)->toBeTrue();
    });
    
    test('tidak bisa edit jika sudah locked', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'is_locked' => true,
        ]);
        
        expect($pembelian->canEdit())->toBeFalse();
    });
    
    test('bisa edit jika belum locked', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'is_locked' => false,
        ]);
        
        expect($pembelian->canEdit())->toBeTrue();
    });
    
    test('bisa hapus meski sudah locked (asal R12 & R13 lolos)', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'is_locked' => true,
            'no_tt' => null,
        ]);
        
        // Lock tidak mempengaruhi permission hapus
        expect($pembelian->canDelete())->toBeTrue();
    });
    
    test('lock final tidak bisa di-undo', function () {
        $pembelian = Pembelian::create([
            'no_po' => 'PO-TEST-' . rand(1000,9999),
            'tanggal' => now(),[
            'id_supplier' => $this->supplier->id,
            'is_locked' => true,
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sudah terkunci');
        
        $pembelian->lockFinal();
    });
});
