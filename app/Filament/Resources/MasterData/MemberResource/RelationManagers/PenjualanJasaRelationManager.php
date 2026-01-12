<?php

namespace App\Filament\Resources\MasterData\MemberResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PenjualanJasaRelationManager extends RelationManager
{
    protected static string $relationship = 'penjualanJasa';

    protected static ?string $title = 'Riwayat Jasa';

    protected static ?string $icon = 'hugeicons-tools';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('no_nota')
            ->columns([
                Tables\Columns\TextColumn::make('penjualan.no_nota')
                    ->label('Nota')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn($record) => $record->penjualan->tanggal_penjualan ? $record->penjualan->tanggal_penjualan->translatedFormat('d F Y') : '-'),

                Tables\Columns\TextColumn::make('jasa.nama_jasa')
                    ->label('Layanan Jasa')
                    ->searchable()
                    ->description(fn($record) => \Illuminate\Support\Str::limit($record->jasa->deskripsi, 50))
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga Satuan')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.'))
                    ->alignRight(),

                Tables\Columns\TextColumn::make('total_row')
                    ->label('Total')
                    ->state(fn($record) => $record->qty * $record->harga)
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.'))
                    ->weight('bold')
                    ->alignRight()
                    ->color('success'),
            ])
            ->striped()
            ->defaultSort('id_penjualan_jasa', 'desc')
            ->modifyQueryUsing(fn($query) => $query
                ->orderBy('tb_penjualan.tanggal_penjualan', 'desc')
                ->orderBy('id_penjualan_jasa', 'desc'))
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn($record) => PenjualanResource::getUrl('view', [
                        'record' => $record->id_penjualan,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(null);
    }
}
