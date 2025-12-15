<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Pages;

use App\Filament\Resources\Akunting\LaporanInputTransaksiResource;
use Filament\Actions;
use Filament\Support\Facades\FilamentView;
use Filament\Resources\Pages\ViewRecord;

class ViewLaporanInputTransaksi extends ViewRecord
{
    protected static string $resource = LaporanInputTransaksiResource::class;

    protected static bool $printHookRegistered = false;

    public function boot(): void
    {
        if (! static::$printHookRegistered) {
            FilamentView::registerRenderHook(
                'panels::head.end',
                fn (): string => view('filament.partials.fi-main-print')->render(),
            );

            static::$printHookRegistered = true;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->extraAttributes([
                    'onclick' => 'window.print(); return false;',
                ]),
        ];
    }
}
