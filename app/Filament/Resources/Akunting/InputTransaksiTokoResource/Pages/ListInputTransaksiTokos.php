<?php

namespace App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use App\Filament\Pages\PengaturanAkunting;
use Filament\Facades\Filament;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInputTransaksiTokos extends ListRecords
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Transaksi')
                ->icon('hugeicons-plus-sign'),
            Actions\Action::make('pengaturan')
                ->label('Pengaturan')
                ->icon('hugeicons-settings-03')
                ->color('gray')
                ->url(fn () => PengaturanAkunting::getUrl(panel: Filament::getCurrentPanel()?->getId())),
        ];
    }
}
