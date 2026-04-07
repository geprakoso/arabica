<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Http\RedirectResponse;

class ProdukJasa extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'hugeicons-package'; // Ikon Menu

    protected static ?string $navigationLabel = 'Produk & Kategori'; // Label di Sidebar

    protected static ?string $title = 'Produk & Kategori'; // Judul Halaman

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4; // Urutan di sidebar

    protected static bool $shouldRegisterNavigation = true;

    // Gunakan view default saja atau kustom
    protected static string $view = 'filament.pages.produkjasa';

    public static function getNavigationUrl(): string
    {
        return ProdukResource::getUrl();
    }

    public function mount(): RedirectResponse
    {
        return redirect()->to(static::getNavigationUrl());
    }
}
