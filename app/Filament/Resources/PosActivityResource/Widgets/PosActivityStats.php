<?php

namespace App\Filament\Resources\PosActivityResource\Widgets;

use App\Models\Penjualan;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class PosActivityStats extends BaseWidget
{
    protected function getCards(): array
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalPenjualan = Penjualan::whereBetween('tanggal_penjualan', [$startOfMonth, $endOfMonth])->count();

        $totalPendapatan = (float) Penjualan::whereBetween('tanggal_penjualan', [$startOfMonth, $endOfMonth])
            ->sum('grand_total');


        $totalProdukTerjual = (int) Penjualan::whereBetween('tanggal_penjualan', [$startOfMonth, $endOfMonth])
            ->withSum('items as qty_terjual', 'qty')
            ->pluck('qty_terjual')
            ->sum();

        return [
            Card::make('Transaksi POS (bulan ini)', number_format($totalPenjualan, 0, ',', '.'))
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->description('Jumlah transaksi selama bulan berjalan'),
            Card::make('Pendapatan (bulan ini)', 'Rp ' . number_format($totalPendapatan, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->description('Grand total terakumulasi bulan ini'),
            Card::make('Produk Terjual (bulan ini)', number_format($totalProdukTerjual, 0, ',', '.'))
                ->icon('heroicon-o-cube')
                ->color('danger')
                ->description('Qty produk dari transaksi bulan ini'),
        ];
    }
}
