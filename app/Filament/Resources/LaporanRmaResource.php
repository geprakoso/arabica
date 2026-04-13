<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanRmaResource\Pages;
use App\Models\Rma;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanRmaResource extends BaseResource
{
    protected static ?string $model = Rma::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan RMA';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $pluralLabel = 'Laporan RMA';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'pembelianItem.pembelian',
                'pembelianItem.produk',
            ]))
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('pembelianItem.produk.nama_produk')
                    ->label('Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('pembelianItem.pembelian.no_po')
                    ->label('No. PO')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('status_garansi')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => RmaResource::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Rma::STATUS_DI_PACKING => 'warning',
                        Rma::STATUS_PROSES_KLAIM => 'info',
                        Rma::STATUS_SELESAI => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('rma_di_mana')
                    ->label('RMA Di Mana')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(40)
                    ->tooltip(fn (Rma $record) => $record->catatan)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('foto_count')
                    ->label('Foto')
                    ->state(fn (Rma $record) => count($record->foto_dokumen ?? []))
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalHeading('Detail RMA')
                    ->infolist(fn (Infolist $infolist) => RmaResource::infolist($infolist)),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanRmas::route('/'),
        ];
    }
}
