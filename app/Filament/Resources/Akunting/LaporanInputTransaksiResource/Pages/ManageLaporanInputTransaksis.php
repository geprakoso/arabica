<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Pages;

use App\Filament\Resources\Akunting\LaporanInputTransaksiResource;
use App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets\LaporanInputTransaksiStats;
use App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets\LaporanInputTransaksiTrendChart;
use App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets\TopExpensesTable;
use Filament\Actions;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanInputTransaksis extends ManageRecords
{
    protected static string $resource = LaporanInputTransaksiResource::class;

    protected static bool $toolbarHookRegistered = false;

    public bool $widgetsCollapsed = false;

    public function boot(): void
    {
        if (! static::$toolbarHookRegistered) {
            FilamentView::registerRenderHook(
                TablesRenderHook::TOOLBAR_START,
                fn (): string => view('filament.partials.laporan-transaksi-toolbar-export')->render(),
            );

            static::$toolbarHookRegistered = true;
        }
    }

    public function mount(): void
    {
        parent::mount();

        $this->widgetsCollapsed = session()->get($this->getWidgetsCollapsedSessionKey(), false);
    }

    // Laporan hanya untuk baca; hilangkan aksi create.
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle-widgets')
                ->label(fn (): string => $this->widgetsCollapsed ? 'Tampilkan Widget' : 'Sembunyikan Widget')
                ->icon(fn (): string => $this->widgetsCollapsed ? 'heroicon-o-arrow-down-circle' : 'heroicon-o-arrow-up-circle')
                ->color('gray')
                ->action(function () {
                    $this->widgetsCollapsed = ! $this->widgetsCollapsed;
                    session()->put($this->getWidgetsCollapsedSessionKey(), $this->widgetsCollapsed);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        if ($this->widgetsCollapsed) {
            return [];
        }

        return [
            LaporanInputTransaksiStats::class,
            LaporanInputTransaksiTrendChart::class,
            TopExpensesTable::class,
        ];
    }

    protected function getWidgetsCollapsedSessionKey(): string
    {
        $userId = auth()->id();

        return 'laporan-input-transaksi.widgets-collapsed.' . ($userId ?? 'guest');
    }
}
