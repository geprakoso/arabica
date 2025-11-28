<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PosActivityResource\Pages;
use App\Filament\Resources\PosActivityResource\Widgets\PosActivityStats;
use App\Models\Penjualan;
use Filament\Facades\Filament;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PosActivityResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationGroup = 'POS';

    protected static ?string $modelLabel = 'Aktivitas';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')->label('Nota')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_penjualan')->date()->label('Tanggal'),
                Tables\Columns\TextColumn::make('grand_total')->money('idr', true)->label('Grand Total'),
                Tables\Columns\TextColumn::make('metode_bayar')->label('Metode Bayar'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn (Penjualan $record) => route('pos.receipt', $record))
                    ->icon('heroicon-o-printer')
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosActivities::route('/'),
            'view' => Pages\ViewPosActivity::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos';
    }

    public static function getWidgets(): array
    {
        return [
            PosActivityStats::class,
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Info Transaksi')
                ->columns(2)
                ->schema([
                    TextEntry::make('no_nota')->label('Nota'),
                    TextEntry::make('tanggal_penjualan')->label('Tanggal'),
                    TextEntry::make('metode_bayar')->label('Metode Bayar')->placeholder('-'),
                    TextEntry::make('grand_total')->label('Grand Total')->money('idr', true),
                    TextEntry::make('tunai_diterima')->label('Tunai Diterima')->money('idr', true)->placeholder('-'),
                    TextEntry::make('kembalian')->label('Kembalian')->money('idr', true)->placeholder('-'),
                    TextEntry::make('member.nama_member')->label('Member')->placeholder('-'),
                    TextEntry::make('karyawan.nama_karyawan')->label('Kasir')->placeholder('-'),
                    TextEntry::make('catatan')->label('Catatan')->columnSpanFull()->placeholder('-'),
                ]),
            Section::make('Item')
                ->schema([
                    RepeatableEntry::make('items')
                        ->schema([
                            Grid::make(4)->schema([
                                TextEntry::make('produk.nama_produk')->label('Produk')->columnSpan(2),
                                TextEntry::make('qty')->label('Qty'),
                                TextEntry::make('harga_jual')->label('Harga')->money('idr', true),
                            ]),
                            Grid::make(4)->schema([
                                TextEntry::make('id_pembelian_item')->label('Batch'),
                                TextEntry::make('kondisi')->label('Kondisi')->placeholder('-'),
                                TextEntry::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->state(fn ($record) => ($record->qty ?? 0) * ($record->harga_jual ?? 0))
                                    ->money('idr', true),
                            ]),
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
