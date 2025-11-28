<?php

namespace App\Filament\Resources\PosActivityResource\Widgets;

use App\Models\Penjualan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PosActivityStats extends BaseWidget
{
    protected function getCards(): array
    {
        $totalPenjualan = Penjualan::count();
        $totalPendapatan = (float) Penjualan::sum('grand_total');
        $totalProdukTerjual = (int) Penjualan::query()
            ->withSum('items as qty_terjual', 'qty')
            ->pluck('qty_terjual')
            ->sum();

        return [
            Card::make('Transaksi POS', number_format($totalPenjualan, 0, ',', '.')),
            Card::make('Pendapatan', 'Rp ' . number_format($totalPendapatan, 0, ',', '.')),
            Card::make('Produk Terjual', number_format($totalProdukTerjual, 0, ',', '.')),
        ];
    }
}
