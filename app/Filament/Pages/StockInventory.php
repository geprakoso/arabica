<?php

namespace App\Filament\Pages;

use App\Filament\Resources\InventoryResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;

class StockInventory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2'; // Ikon Menu
    protected static ?string $navigationLabel = 'Inventory & Stock'; // Label di Sidebar
    protected static ?string $title = 'Inventory & Stock'; // Judul Halaman
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 1; // Urutan di sidebar
    protected static bool $shouldRegisterNavigation = true;
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
