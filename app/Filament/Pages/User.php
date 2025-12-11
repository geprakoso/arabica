<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\MemberResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class User extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'hugeicons-contact'; // Ikon Menu
    protected static ?string $navigationLabel = 'User & Supplier'; // Label di Sidebar
    protected static ?string $title = 'User'; // Judul Halaman
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3; // Urutan di sidebar
    protected static bool $shouldRegisterNavigation = true; // tampilkan di sidebar
    // Gunakan view default saja atau kustom
    protected static string $view = 'filament.pages.user';

    public static function getNavigationUrl(): string
    {
        return MemberResource::getUrl();
    }

    public function mount(): RedirectResponse
    {
        return redirect()->to(static::getNavigationUrl());
    }
}
