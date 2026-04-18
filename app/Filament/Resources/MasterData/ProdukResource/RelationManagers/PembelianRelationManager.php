<?php

namespace App\Filament\Resources\MasterData\ProdukResource\RelationManagers;

use App\Filament\Resources\PembelianResource;
use App\Models\PembelianItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PembelianRelationManager extends RelationManager
{
    protected static string $relationship = 'pembelianItems';

    protected static ?string $title = 'Riwayat Pembelian';

    protected static ?string $icon = 'heroicon-m-arrow-down-tray';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_po')
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['pembelian.supplier', 'pembelian.karyawan'])
                ->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('pembelian.no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->description(fn (PembelianItem $record) => $record->pembelian?->tanggal ? $record->pembelian->tanggal->translatedFormat('d F Y') : '-'),

                TextColumn::make('pembelian.nota_supplier')
                    ->label('Nota Supplier')
                    ->icon('heroicon-o-receipt-percent')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('pembelian.supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->placeholder('-'),

                TextColumn::make('pembelian.karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('hpp')
                    ->label('HPP')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) ($state ?? 0), 0, ',', '.'))
                    ->alignRight(),

                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'baru' => 'success',
                        'bekas' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('total_item')
                    ->label('Total')
                    ->state(fn (PembelianItem $record) => (int) ($record->hpp ?? 0) * (int) ($record->qty ?? 0))
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) ($state ?? 0), 0, ',', '.'))
                    ->weight('bold')
                    ->alignRight()
                    ->color('success'),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('kondisi')
                    ->label('Kondisi')
                    ->options([
                        'baru' => 'Baru',
                        'bekas' => 'Bekas',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn (PembelianItem $record) => $record->pembelian ? PembelianResource::getUrl('view', ['record' => $record->pembelian]) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (PembelianItem $record) => ! $record->pembelian),
            ])
            ->recordUrl(null);
    }
}
