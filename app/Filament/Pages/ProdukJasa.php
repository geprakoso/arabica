<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ProdukJasa extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'hugeicons-package'; // Ikon Menu
    protected static ?string $navigationLabel = 'Produk & Jasa'; // Label di Sidebar
    protected static ?string $title = 'Produk & Jasa'; // Judul Halaman
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1; // Urutan di sidebar
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
