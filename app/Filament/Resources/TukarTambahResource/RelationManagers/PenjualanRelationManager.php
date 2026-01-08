<?php

namespace App\Filament\Resources\TukarTambahResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use App\Models\Penjualan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenjualanRelationManager extends RelationManager
{
    protected static string $relationship = 'penjualan';

    protected static ?string $title = 'Penjualan';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query
                ->with(['items', 'jasaItems'])
                ->withSum('pembayaran', 'jumlah'))
            ->recordUrl(fn(Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);
                        $sisa = max(0, $grandTotal - $totalPaid);

                        return $sisa > 0 ? 'Belum Lunas' : 'Lunas';
                    })
                    ->color(fn(string $state): string => $state === 'Lunas' ? 'success' : 'danger'),
                TextColumn::make('sisa_bayar_display')
                    ->label('Sisa Bayar')
                    ->alignRight()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);

                        $sisa = max(0, $grandTotal - $totalPaid);

                        return 'Rp ' . number_format((int) $sisa, 0, ',', '.');
                    }),
                TextColumn::make('grand_total_display')
                    ->label('Grand Total')
                    ->weight('bold')
                    ->color('success')
                    ->alignRight()
                    ->state(function (Penjualan $record): string {
                        $subtotalProduk = (float) ($record->items
                            ? $record->items->sum(fn($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0))
                            : 0);
                        $subtotalJasa = (float) ($record->jasaItems
                            ? $record->jasaItems->sum(fn($item) => (int) ($item->harga ?? 0) * (int) ($item->qty ?? 0))
                            : 0);
                        $diskon = (float) ($record->diskon_total ?? 0);
                        $grandTotal = max(0, $subtotalProduk + $subtotalJasa - $diskon);

                        return 'Rp ' . number_format((int) $grandTotal, 0, ',', '.');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn(Penjualan $record) => PenjualanResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view')
                    ->label('Show')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
