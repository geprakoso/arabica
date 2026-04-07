<?php

namespace App\Filament\Resources\TukarTambahResource\RelationManagers;

use App\Filament\Resources\PembelianResource;
use App\Models\Pembelian;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PembelianRelationManager extends RelationManager
{
    protected static string $relationship = 'pembelian';

    protected static ?string $title = 'Pembelian (Barang Masuk)';

    protected static ?string $icon = 'heroicon-m-arrow-down-tray';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_po')
            ->modifyQueryUsing(fn($query) => $query->with('items'))
            ->recordUrl(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->description(fn(Pembelian $record) => $record->tanggal ? $record->tanggal->translatedFormat('d F Y') : '-'),

                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->placeholder('-'),

                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('total_qty')
                    ->label('Jumlah')
                    ->badge()
                    ->color('gray')
                    ->alignCenter()
                    ->state(fn(Pembelian $record): string => (string) ($record->items
                        ? (int) $record->items->sum(fn($item) => (int) ($item->qty ?? 0))
                        : 0)),

                TextColumn::make('grand_total_display')
                    ->label('Total Akhir')
                    ->alignRight()
                    ->weight('bold')
                    ->color('success')
                    ->state(function (Pembelian $record): string {
                        $total = $record->items
                            ? $record->items->sum(fn($item) => (int) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))
                            : 0;

                        return 'Rp ' . number_format((int) $total, 0, ',', '.');
                    }),
            ])
            ->striped()
            ->defaultSort('tanggal', 'desc')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
