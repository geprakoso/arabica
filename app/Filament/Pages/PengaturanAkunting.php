<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use App\Filament\Resources\Akunting\KodeAkunResource;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class PengaturanAkunting extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pengaturan-akunting';

    public string $activeTab = 'kode_akun';
    protected array $queryString = ['activeTab'];

    public function mount(): void
    {
        $this->activeTab = request()->query('activeTab', $this->activeTab);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
