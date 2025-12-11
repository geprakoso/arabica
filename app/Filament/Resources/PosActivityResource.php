<?php

namespace App\Filament\Resources;

use App\Enums\MetodeBayar;
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
                Tables\Columns\TextColumn::make('metode_bayar')
                    ->label('Metode Bayar')
                    ->badge()
                    ->color(fn (MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'success',
                        MetodeBayar::CARD => 'info',
                        MetodeBayar::TRANSFER => 'gray',
                        MetodeBayar::EWALLET => 'warning',
                        default => 'secondary',
                    })
                    ->icon(fn (MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'heroicon-o-currency-dollar',
                        MetodeBayar::CARD => 'heroicon-o-credit-card',
                        MetodeBayar::TRANSFER => 'heroicon-o-banknotes',
                        MetodeBayar::EWALLET => 'heroicon-o-wallet',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (?MetodeBayar $state) => $state?->label()),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn(Penjualan $record) => route('pos.receipt', $record))
                    ->icon('heroicon-o-printer')
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosActivities::route('/'),
            'view' => Pages\ViewPosActivity::route('/{record}'),
        ];
    }

    /**
     * Menentukan apakah resource ini harus terdaftar di navigasi.
     *
     * Fungsi ini memeriksa apakah panel yang sedang aktif adalah panel 'pos'.
     * Jika ya, maka resource ini akan terdaftar di navigasi.
     *
     * @return bool True jika panel saat ini adalah 'pos', false jika tidak.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos';
    }

    // mendapatkan widget yang akan di tampilkan di resource
    public static function getWidgets(): array
    {
        return [
            PosActivityStats::class,
        ];
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Info Transaksi')
                    ->columns(4)
                    ->compact()
                    ->description('Informasi Transaksi')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        TextEntry::make('no_nota')->label('Nota'),
                        TextEntry::make('tanggal_penjualan')->label('Tanggal'),

                        TextEntry::make('member.nama_member')->label('Member')->placeholder('-'),
                        TextEntry::make('karyawan.nama_karyawan')->label('Kasir')->placeholder('-'),
                        TextEntry::make('catatan')->label('Catatan')->columnSpanFull()->placeholder('-'),

                        section::make('')

                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('metode_bayar')
                                        ->label('Metode Bayar')
                                        ->state(fn (?MetodeBayar $state) => $state?->label())
                                        ->placeholder('-'),
                                    TextEntry::make('grand_total')->label('Grand Total')->currency('IDR'),
                                    TextEntry::make('tunai_diterima')->label('Tunai Diterima')->currency('IDR')->placeholder('-'),
                                    TextEntry::make('kembalian')->label('Kembalian')->currency('IDR')->placeholder('-'),
                                ]),
                            ])

                    ]),
                Section::make('Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('produk.nama_produk')->label('Produk')
                                    ->columnSpan(2)
                                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                                    TextEntry::make('qty')->label('Qty'),
                                    TextEntry::make('harga_jual')->label('Harga')->currency('IDR'),
                                ]),
                                Grid::make(4)->schema([
                                    TextEntry::make('id_pembelian_item')->label('Batch'),
                                    TextEntry::make('kondisi')->label('Kondisi')->placeholder('-'),
                                    TextEntry::make('subtotal_display')
                                        ->label('Subtotal')
                                        ->state(fn($record) => ($record->qty ?? 0) * ($record->harga_jual ?? 0) * 100)
                                        ->currency('IDR'),
                                ]),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
