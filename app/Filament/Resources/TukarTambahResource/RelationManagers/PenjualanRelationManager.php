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

    protected static ?string $title = 'Penjualan (Barang Keluar)';

    protected static ?string $icon = 'heroicon-m-arrow-up-tray';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_nota')
            ->modifyQueryUsing(fn($query) => $query
                ->with(['items', 'jasaItems'])
                ->withSum('pembayaran', 'jumlah'))
            ->recordUrl(fn(Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('no_nota')
                    ->label('Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->description(fn(Penjualan $record) => $record->tanggal_penjualan ? $record->tanggal_penjualan->translatedFormat('d F Y') : '-'),

                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->icon('heroicon-m-user')
                    ->weight('medium')
                    ->placeholder('-'),

                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user-circle')
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'lunas' => 'success',
                        'belum_lunas' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('sisa_bayar_display')
                    ->label('Sisa Tagihan')
                    ->alignRight()
                    ->color('danger')
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);

                        $sisa = max(0, $grandTotal - $totalPaid);
                        if ($sisa <= 0) return '-';

                        return 'Rp ' . number_format((int) $sisa, 0, ',', '.');
                    }),

                TextColumn::make('grand_total_display')
                    ->label('Total Akhir')
                    ->weight('bold')
                    ->color('success')
                    ->alignRight()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        return 'Rp ' . number_format((int) $grandTotal, 0, ',', '.');
                    }),
            ])
            ->striped()
            ->defaultSort('tanggal_penjualan', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn(Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->url(fn(Penjualan $record) => PenjualanResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
