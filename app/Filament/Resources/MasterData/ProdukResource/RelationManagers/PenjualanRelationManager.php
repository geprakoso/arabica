<?php

namespace App\Filament\Resources\MasterData\ProdukResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use App\Models\PenjualanItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PenjualanRelationManager extends RelationManager
{
    protected static string $relationship = 'penjualanItems';

    protected static ?string $title = 'Riwayat Penjualan';

    protected static ?string $icon = 'heroicon-m-shopping-cart';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_nota')
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['penjualan.member', 'penjualan.karyawan'])
                ->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('penjualan.no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->description(fn (PenjualanItem $record) => $record->penjualan?->tanggal_penjualan ? $record->penjualan->tanggal_penjualan->translatedFormat('d F Y') : '-'),

                TextColumn::make('penjualan.member.nama_member')
                    ->label('Member/Pelanggan')
                    ->icon('heroicon-m-user-group')
                    ->weight('medium')
                    ->placeholder('Umum')
                    ->searchable(),

                TextColumn::make('penjualan.karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
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
                    ->state(fn (PenjualanItem $record) => (int) ($record->qty ?? 0) * (int) ($record->harga_jual ?? 0))
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) ($state ?? 0), 0, ',', '.'))
                    ->weight('bold')
                    ->alignRight()
                    ->color('success'),

                TextColumn::make('penjualan.status_pembayaran')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'lunas' => 'success',
                        'belum_lunas' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => $state === 'lunas' ? 'LUNAS' : 'BELUM LUNAS'),
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
                    ->url(fn (PenjualanItem $record) => $record->penjualan ? PenjualanResource::getUrl('view', ['record' => $record->penjualan]) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (PenjualanItem $record) => ! $record->penjualan),
            ])
            ->recordUrl(null);
    }
}
