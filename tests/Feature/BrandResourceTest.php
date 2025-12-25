<?php

use App\Models\Brand;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup storage fake untuk testing file upload
    Storage::fake('public');

    // Create authenticated user untuk testing
    $this->user = User::factory()->create(); // buat user
    $role = Role::firstOrCreate([
        'name' => config('filament-shield.super_admin.name', 'super_admin'),
        'guard_name' => config('auth.defaults.guard', 'web'),
    ]);
    $this->user->assignRole($role); // masukkan role ke user
    $this->actingAs($this->user); // autentikasi sebagai user tersebut
    Filament::setCurrentPanel(Filament::getPanel('admin')); // set panel Filament ke admin
    
});

// buat test untuk halaman create brand
describe('Brand Resource - Create', function () {

    test('can render create page', function () {
        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->assertSuccessful();
    });

    test('can create brand with valid data', function () {
        $brandData = [
            'nama_brand' => 'nike',
            'slug' => 'nike',
            'is_active' => true,
        ];

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm($brandData)
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify brand was created in database
        $this->assertDatabaseHas('md_brand', [
            'nama_brand' => 'Nike', // Should be title cased
            'slug' => 'Nike',
            'is_active' => true,
        ]);

        expect(Brand::where('nama_brand', 'Nike')->first())
            ->not->toBeNull()
            ->nama_brand->toBe('Nike')
            ->slug->toBe('Nike')
            ->is_active->toBeTrue();
    });

    // buat test untuk validasi nama_brand harus di title case
    test('nama_brand is automatically title cased on create', function () {
        $brandData = [
            'nama_brand' => 'adidas sportswear',
            'slug' => 'adidas',
        ];

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm($brandData)
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('slug', 'adidas')->first(); 

        expect($brand->nama_brand)->toBe('Adidas Sportswear');
    });

    // buat test untuk validasi slug di generate otomatis dari nama_brand jika tidak diisi
    test('slug is automatically generated from nama_brand if not provided', function () {
        $brandData = [
            'nama_brand' => 'Puma Indonesia',
        ];

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm($brandData)
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('nama_brand', 'Puma Indonesia')->first(); //

        expect($brand)
            ->not->toBeNull()
            ->slug->toBe('puma-indonesia');
    });

    test('can create brand with logo upload', function () {
        $file = UploadedFile::fake()->image('nike-logo.jpg');

        $brandData = [
            'nama_brand' => 'Nike',
            'slug' => 'nike',
            'logo_url' => $file,
        ];

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm($brandData)
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('nama_brand', 'Nike')->first();

        expect($brand) 
            ->not->toBeNull()
            ->logo_url->not->toBeNull();

        // Verify file was stored
        Storage::disk('public')->assertExists($brand->logo_url);
    });

    // buat test untuk validasi nama_brand harus diisi
    test('nama_brand is required', function () {
        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm([
                'nama_brand' => '', // kosongkan nama_brand
            ])
            ->call('create') // klik tombol create
            ->assertHasFormErrors(['nama_brand' => 'required']); // pastikan ada error required
    });

    // buat test untuk validasi slug harus unik
    test('slug must be unique', function () {
        
        // buat brand pertama
        Brand::create([
            'nama_brand' => 'Nike',
            'slug' => 'nike',
        ]);

        // buat brand kedua dengan slug yang sama
        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm([
                'nama_brand' => 'Nike Sportswear',
                'slug' => 'nike',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    });

    test('is_active defaults to true', function () {
        $brandData = [
            'nama_brand' => 'Adidas',
        ];

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm($brandData)
            ->call('create')
            ->assertHasNoFormErrors();

        $brand = Brand::where('nama_brand', 'Adidas')->first();

        expect($brand->is_active)->toBeTrue();
    });

    test('logo upload only accepts image files', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        livewire(\App\Filament\Resources\MasterData\BrandResource\Pages\CreateBrand::class)
            ->fillForm([
                'nama_brand' => 'Test Brand',
                'logo_url' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['logo_url']);
    });
});

describe('Brand Model - Auto Slug Generation', function () {

    test('slug is auto-generated when saving brand without slug', function () {
        $brand = Brand::create([
            'nama_brand' => 'Under Armour',
        ]);

        expect($brand->slug)->toBe('under-armour');
    });

    test('slug is not overwritten if already provided', function () {
        $brand = Brand::create([
            'nama_brand' => 'Under Armour',
            'slug' => 'ua-brand',
        ]);

        expect($brand->slug)->toBe('ua-brand');
    });

    test('slug is generated from nama_brand with special characters', function () {
        $brand = Brand::create([
            'nama_brand' => 'Nike & Adidas Co.',
        ]);

        expect($brand->slug)->toBe('nike-adidas-co');
    });
});

describe('Brand Model - CRUD Operations', function () {

    test('can create brand directly via model', function () {
        $brand = Brand::create([
            'nama_brand' => 'Reebok',
            'slug' => 'reebok',
            'is_active' => true,
        ]);

        expect($brand)
            ->id->toBeInt()
            ->nama_brand->toBe('Reebok')
            ->slug->toBe('reebok')
            ->is_active->toBeTrue();

        $this->assertDatabaseHas('md_brand', [
            'nama_brand' => 'Reebok',
            'slug' => 'reebok',
        ]);
    });

    test('can update brand', function () {
        $brand = Brand::create([
            'nama_brand' => 'Nike',
            'slug' => 'nike',
        ]);

        $brand->update([
            'nama_brand' => 'Nike Sportswear',
            'slug' => 'nike-sportswear',
        ]);

        expect($brand->fresh())
            ->nama_brand->toBe('Nike Sportswear')
            ->slug->toBe('nike-sportswear');
    });

    test('can delete brand', function () {
        $brand = Brand::create([
            'nama_brand' => 'Puma',
            'slug' => 'puma',
        ]);

        $brandId = $brand->id;
        $brand->delete();

        expect(Brand::find($brandId))->toBeNull();

        $this->assertDatabaseMissing('md_brand', [
            'id' => $brandId,
        ]);
    });

    test('can toggle is_active status', function () {
        $brand = Brand::create([
            'nama_brand' => 'Adidas',
            'slug' => 'adidas',
            'is_active' => true,
        ]);

        expect($brand->is_active)->toBeTrue();

        $brand->update(['is_active' => false]);

        expect($brand->fresh()->is_active)->toBeFalse();
    });
});

describe('Brand Model - Validation', function () {

    test('nama_brand must be unique', function () {
        Brand::create([
            'nama_brand' => 'Nike',
            'slug' => 'nike',
        ]);

        // This will throw exception due to unique constraint
        expect(fn() => Brand::create([
            'nama_brand' => 'Nike',
            'slug' => 'nike-2',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    test('slug must be unique', function () {
        Brand::create([
            'nama_brand' => 'Nike',
            'slug' => 'nike',
        ]);

        // This will throw exception due to unique constraint
        expect(fn() => Brand::create([
            'nama_brand' => 'Nike Sportswear',
            'slug' => 'nike',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });
});
