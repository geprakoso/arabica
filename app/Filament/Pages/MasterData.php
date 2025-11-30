<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\ProdukResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;

class MasterDatas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2'; // Ikon Menu
    protected static ?string $navigationLabel = 'Master Data'; // Label di Sidebar
    protected static ?string $title = 'Master Data'; // Judul Halaman
    protected static ?int $navigationSort = 3; // Urutan di sidebar
    protected static bool $shouldRegisterNavigation = true;
    // Gunakan view default saja atau kustom
    protected static string $view = 'filament.pages.master-data';

    public static function getNavigationUrl(): string
    {
        return ProdukResource::getUrl();
    }

    public function mount(): RedirectResponse
    {
        return redirect()->to(static::getNavigationUrl());
    }
}
