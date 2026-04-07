<?php

use App\Models\Brand;
use App\Models\Kategori;
use App\Models\Produk;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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

describe('Produk Resource - Create', function () {
    test('render create page', function () {
        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->assertSuccessful();
    });

    test('buat produk dengan data valid', function () {
        $produkData = [
            'nama_produk' => 'kopi arabica',
            'deskripsi' => 'Biji kopi pilihan.',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => Produk::generateSku(),
            'berat' => 120,
            'panjang' => 10,
            'lebar' => 5,
            'tinggi' => 2,
        ];

        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->fillForm($produkData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('md_produk', [
            'nama_produk' => 'KOPI ARABICA',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => $produkData['sku'],
        ]);
    });

    test('test nama brand uppercase', function () {
        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->fillForm([
                'nama_produk' => 'kopi gayo',
                'kategori_id' => $this->kategori->id,
                'brand_id' => $this->brand->id,
                'sku' => Produk::generateSku(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $produk = Produk::where('nama_produk', 'KOPI GAYO')->first();

        expect($produk)->not->toBeNull();
    });

    test('SKU harus unik', function () {
        Produk::create([
            'nama_produk' => 'KOPI LAMPUNG',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP9999',
        ]);

        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->fillForm([
                'nama_produk' => 'kopi lampung 2',
                'kategori_id' => $this->kategori->id,
                'brand_id' => $this->brand->id,
                'sku' => 'MDP9999',
            ])
            ->call('create')
            ->assertHasFormErrors(['sku' => 'unique']);
    });

    test('required fields must be provided', function () {
        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->fillForm([
                'nama_produk' => '',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'nama_produk' => 'required',
                'kategori_id' => 'required',
                'brand_id' => 'required',
            ]);
    });

    test('dapat upload gambar', function () {
        $file = UploadedFile::fake()->image('produk.jpg');

        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\CreateProduk::class)
            ->fillForm([
                'nama_produk' => 'kopi toraja',
                'kategori_id' => $this->kategori->id,
                'brand_id' => $this->brand->id,
                'sku' => Produk::generateSku(),
                'image_url' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $produk = Produk::where('nama_produk', 'KOPI TORAJA')->first();

        expect($produk)
            ->not->toBeNull()
            ->image_url->not->toBeNull();

        Storage::disk('public')->assertExists($produk->image_url);
    });
});

describe('Produk Resource - Edit', function () {
    test('nama produk dapat diubah', function () {
        $produk = Produk::create([
            'nama_produk' => 'KOPI MANADO',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
            'sku' => 'MDP1111',
        ]);

        livewire(\App\Filament\Resources\MasterData\ProdukResource\Pages\EditProduk::class, [
            'record' => $produk->getRouteKey(),
        ])
            ->fillForm([
                'nama_produk' => 'kopi manado premium',
                'kategori_id' => $this->kategori->id,
                'brand_id' => $this->brand->id,
                'sku' => $produk->sku,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($produk->fresh()->nama_produk)->toBe('KOPI MANADO PREMIUM');
    });
});

describe('Produk Model - Auto SKU Generation', function () {
    test('sku is auto-generated when creating produk without sku', function () {
        $produk = Produk::create([
            'nama_produk' => 'KOPI BALI',
            'kategori_id' => $this->kategori->id,
            'brand_id' => $this->brand->id,
        ]);

        expect($produk->sku)->toMatch('/^MDP\d{4}$/');
    });
});
