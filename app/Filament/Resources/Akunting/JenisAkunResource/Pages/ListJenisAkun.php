<?php

namespace App\Filament\Resources\Akunting\JenisAkunResource\Pages;

use App\Filament\Resources\Akunting\JenisAkunResource;
use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;

class ListJenisAkun extends ListRecords
{
    protected static string $resource = JenisAkunResource::class;

    protected static bool $toolbarHookRegistered = false;
    protected static bool $shouldShowBreadcrumbs = true;

    public function mount(): void
        {
            parent::mount();
            
                    if (! static::$toolbarHookRegistered) {
                                FilamentView::registerRenderHook(
                                    TablesRenderHook::TOOLBAR_REORDER_TRIGGER_AFTER,
                                    fn () => view('filament.partials.jenis-akun-toolbar-create', [
                                        'createUrl' => JenisAkunResource::getUrl('create', panel: Filament::getCurrentPanel()?->getId()),
                                    ]),
                                );
                    
                                static::$toolbarHookRegistered = true;
                            }
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): \Illuminate\Contracts\Support\Htmlable | string
    {
        return 'Jenis Akun';
    }

    public function getBreadcrumbs(): array
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return [
            InputTransaksiTokoResource::getUrl(panel: $panelId) => 'Input Transaksi Toko',
            static::getResource()::getUrl(panel: $panelId) => 'Jenis Akun',
            'List',
        ];
    }
}
