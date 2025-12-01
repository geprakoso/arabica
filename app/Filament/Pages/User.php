<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MasterData\MemberResource;
use Illuminate\Http\RedirectResponse;
use Filament\Pages\Page;

class User extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2'; // Ikon Menu
    protected static ?string $navigationLabel = 'User'; // Label di Sidebar
    protected static ?string $title = 'User'; // Judul Halaman
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 3; // Urutan di sidebar
    protected static bool $shouldRegisterNavigation = true;
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
