<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\AkunTransaksiResource;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Http\RedirectResponse;

class AkunKeuangan extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'hugeicons-package'; // Ikon Menu

    protected static ?string $navigationLabel = 'Akun Keuangan'; // Label di Sidebar

    protected static ?string $title = 'Akun Keuangan'; // Judul Halaman

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3; // Urutan di sidebar

    protected static bool $shouldRegisterNavigation = true;

    // Gunakan view default saja atau kustom
    protected static string $view = 'filament.pages.akunkeuangan';

    public static function getNavigationUrl(): string
    {
        return AkunTransaksiResource::getUrl();
    }

    public function mount(): RedirectResponse
    {
        return redirect()->to(static::getNavigationUrl());
    }
}
