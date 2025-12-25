<?php

use App\Filament\Resources\InventoryResource;
use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $role = Role::firstOrCreate([
        'name' => config('filament-shield.super_admin.name', 'super_admin'),
        'guard_name' => config('auth.defaults.guard', 'web'),
    ]);
    $this->user->assignRole($role);
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->kategori = Kategori::create([
        'nama_kategori' => 'Biji Kopi',
        'slug' => 'biji-kopi',
    ]);

    $this->brand = Brand::create([
        'nama_brand' => 'Arabica',
        'slug' => 'arabica',
    ]);
});

describe('Inventory Resource - List', function () {
    test('bisa diakses', function () {
        livewire(\App\Filament\Resources\InventoryResource\Pages\ListInventories::class)
            ->assertSuccessful();
    });

    test('tampilan infolist', function () {
        $produk = Produk::create([
            'nama_produk' => 'KOPI ACEH',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP3001',
        ]);

        $pembelian = Pembelian::create([
            'no_po' => 'PO-3001',
            'tanggal' => now()->subDay()->toDateString(),
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelian->id_pembelian,
            'id_produk' => $produk->id,
            'qty' => 5,
            'hpp' => 12000,
            'harga_jual' => 20000,
            'kondisi' => 'baru',
        ]);

        livewire(\App\Filament\Resources\InventoryResource\Pages\ListInventories::class)
            ->mountTableAction('detail', $produk)
            ->assertTableActionMounted('detail')
            ->assertSee('Detail Inventory')
            ->assertSee('Kopi Aceh')
            ->assertSuccessful();
    });

    

    test('menampilkan produk yang masih aktif', function () {
        $produkAktif = Produk::create([
            'nama_produk' => 'KOPI ACEH',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP1001',
        ]);

        $produkHabis = Produk::create([
            'nama_produk' => 'KOPI SUMBAWA',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP1002',
        ]);

        $pembelianAktif = Pembelian::create([
            'no_po' => 'PO-1001',
            'tanggal' => now()->subDay(),
        ]);

        $pembelianHabis = Pembelian::create([
            'no_po' => 'PO-1002',
            'tanggal' => now()->subDay(),
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelianAktif->id_pembelian,
            'id_produk' => $produkAktif->id,
            'qty' => 5,
            'hpp' => 12000,
            'harga_jual' => 20000,
            'kondisi' => 'baru',
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelianHabis->id_pembelian,
            'id_produk' => $produkHabis->id,
            'qty' => 0,
            'hpp' => 10000,
            'harga_jual' => 18000,
            'kondisi' => 'baru',
        ]);

        $records = InventoryResource::getEloquentQuery()->get();

        expect($records->pluck('id'))
            ->toContain($produkAktif->id)
            ->not->toContain($produkHabis->id);
    });
});

describe('Inventory Resource - Snapshot', function () {
    test('menampilkan inventory snapshot', function () {
        $produk = Produk::create([
            'nama_produk' => 'KOPI FLORES',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP2001',
        ]);

        $pembelianLama = Pembelian::create([
            'no_po' => 'PO-2001',
            'tanggal' => now()->subDays(3)->toDateString(),
        ]);

        $pembelianBaru = Pembelian::create([
            'no_po' => 'PO-2002',
            'tanggal' => now()->subDay()->toDateString(),
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelianLama->id_pembelian,
            'id_produk' => $produk->id,
            'qty' => 3,
            'hpp' => 11000,
            'harga_jual' => 19000,
            'kondisi' => 'baru',
        ]);

        PembelianItem::create([
            'id_pembelian' => $pembelianBaru->id_pembelian,
            'id_produk' => $produk->id,
            'qty' => 7,
            'hpp' => 12500,
            'harga_jual' => 21000,
            'kondisi' => 'baru',
        ]);

        

        $reflection = new ReflectionMethod(InventoryResource::class, 'getInventorySnapshot');
        $reflection->setAccessible(true);
        $snapshot = $reflection->invoke(null, $produk);

        expect($snapshot['qty'])->toBe(10);
        expect($snapshot['batch_count'])->toBe(2);
        expect($snapshot['latest_batch']['hpp'])->toBe(12500.0);
        expect($snapshot['latest_batch']['harga_jual'])->toBe(21000.0);
        expect($snapshot['latest_batch']['tanggal'])->toBe($pembelianBaru->tanggal->format('d M Y'));
    });

});
