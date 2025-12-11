<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InventoryResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class StockInventory extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box'; // Ikon Menu
    protected static ?string $navigationLabel = 'Inventory & Stock'; // Label di Sidebar
    protected static ?string $title = 'Inventory & Stock'; // Judul Halaman
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 1; // Urutan di sidebar
    protected static bool $shouldRegisterNavigation = false; // hide navigation parent item
    // Gunakan view default saja atau kustom
    protected static string $view = 'filament.pages.inventorystock';

    public static function getNavigationUrl(): string
    {
        return InventoryResource::getUrl();
    }

    public function mount(): RedirectResponse
    {
        return redirect()->to(static::getNavigationUrl());
    }
}
