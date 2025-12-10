<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use App\Filament\Resources\Akunting\KodeAkunResource;
use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;

class PengaturanAkunting extends Page
{
    // Ikon default (tidak dipakai di nav karena halaman tidak terdaftar di sidebar).
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    // Blade view yang dirender untuk halaman ini.
    protected static string $view = 'filament.pages.pengaturan-akunting';

    // Tab aktif saat ini; default ke kode_akun.
    public string $activeTab = 'kode_akun';
    // Simpan state tab di query string agar persist setelah refresh.
    protected array $queryString = ['activeTab'];

    // Total record untuk badge tab Kode Akun.
    public int $kodeAkunCount = 0;
    // Total record untuk badge tab Jenis Akun.
    public int $jenisAkunCount = 0;

    public function mount(): void
    {
        // Normalisasi tab dari query string saat pertama kali dimuat.
        $this->activeTab = $this->resolveActiveTab(request()->query('activeTab', $this->activeTab));
        // Muat jumlah record untuk badge tab.
        $this->loadTabBadges();
    }

    public function updatedActiveTab(string $tab): void
    {
        // Pastikan hanya nilai tab yang valid yang digunakan saat berubah.
        $this->activeTab = $this->resolveActiveTab($tab);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getBreadcrumbs(): array
    {
        // Pastikan breadcrumb memakai panel yang sedang aktif.
        $panelId = Filament::getCurrentPanel()?->getId();

        return [
            // Langkah 1: Dashboard.
            Dashboard::getUrl(panel: $panelId) => Dashboard::getNavigationLabel(),
            // Langkah 2: Menu utama resource Input Transaksi Toko.
            InputTransaksiTokoResource::getUrl(panel: $panelId) => InputTransaksiTokoResource::getNavigationLabel(),
            // Langkah 3: Halaman ini.
            static::getNavigationLabel(),
        ];
    }

    protected function resolveActiveTab(?string $tab): string
    {
        // Mapping berbagai ejaan ke nilai tab standar.
        return match (strtolower((string) $tab)) {
            'kode_akun', 'kodeakun' => 'kode_akun',
            'jenis_akun', 'jenisakun' => 'jenis_akun',
            default => 'kode_akun',
        };
    }

    protected function loadTabBadges(): void
    {
        // Hitung total untuk masing-masing tab; dapat dipanggil ulang jika perlu refresh.
        $this->kodeAkunCount = KodeAkun::count();
        $this->jenisAkunCount = JenisAkun::count();
    }
}
