<?php

namespace App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use App\Filament\Pages\PengaturanAkunting;
use App\Enums\KategoriAkun;
use Filament\Facades\Filament;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInputTransaksiTokos extends ListRecords
{
    protected static string $resource = InputTransaksiTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Transaksi')
                ->icon('hugeicons-plus-sign'),
            // Actions\Action::make('pengaturan')
            //     ->label('Pengaturan')
            //     ->icon('hugeicons-settings-03')
            //     ->color('gray')
            //     ->url(fn () => PengaturanAkunting::getUrl(panel: Filament::getCurrentPanel()?->getId())),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'aktiva' => Tab::make('Aktiva')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kategori_transaksi', KategoriAkun::Aktiva->value)),
            'pasiva' => Tab::make('Pasiva')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kategori_transaksi', KategoriAkun::Pasiva->value)),
            'pendapatan' => Tab::make('Pendapatan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kategori_transaksi', KategoriAkun::Pendapatan->value)),
            'beban' => Tab::make('Beban')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kategori_transaksi', KategoriAkun::Beban->value)),
        ];
    }
}
