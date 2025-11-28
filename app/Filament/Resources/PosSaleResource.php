<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PosSaleResource\Pages;
use App\Models\Gudang;
use App\Models\Member;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Produk;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PosSaleResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'POS';

    protected static ?string $modelLabel = 'POS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penjualan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_penjualan')
                            ->required()
                            ->native(false)
                            ->default(now()),
                        Forms\Components\Select::make('id_member')
                            ->label('Member')
                            ->options(Member::query()->pluck('nama_member', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Select::make('gudang_id')
                            ->label('Gudang')
                            ->options(Gudang::query()->pluck('nama_gudang', 'id'))
                            ->searchable()
                            ->nullable(),
                        Forms\Components\Textarea::make('catatan')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Keranjang')
                    ->schema([
                        TableRepeater::make('items')
                            ->label('Item')
                            ->minItems(1)
                            ->columnSpanFull()
                            ->childComponents([
                                Forms\Components\Select::make('id_produk')
                                    ->label('Produk')
                                    ->options(function () {
                                        $qtyColumn = PembelianItem::qtySisaColumn();

                                        return Produk::query()
                                            ->whereHas('pembelianItems', fn ($q) => $q->where($qtyColumn, '>', 0))
                                            ->orderBy('nama_produk')
                                            ->pluck('nama_produk', 'id');
                                    })
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('id_pembelian_item')
                                    ->label('Batch')
                                    ->options(function (Get $get) {
                                        $qtyColumn = PembelianItem::qtySisaColumn();
                                        $productColumn = PembelianItem::productForeignKey();
                                        $productId = $get('id_produk');

                                        $query = PembelianItem::query()
                                            ->with('produk')
                                            ->where($qtyColumn, '>', 0)
                                            ->orderByDesc('id_pembelian_item');

                                        if ($productId) {
                                            $query->where($productColumn, $productId);
                                        }

                                        return $query->get()->mapWithKeys(fn ($item) => [
                                            $item->id_pembelian_item => ($item->produk?->nama_produk ?? 'Produk') .
                                                ' | Batch #' . $item->id_pembelian_item .
                                                ' | Stok: ' . ($item->{$qtyColumn} ?? 0),
                                        ]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->helperText('Pilih batch agar stok berkurang sesuai pembelian.'),
                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('harga_jual')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->helperText('Kosongkan untuk pakai harga default batch.')
                                    ->nullable(),
                                Forms\Components\TextInput::make('diskon')
                                    ->label('Diskon')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->nullable(),
                                Forms\Components\TextInput::make('kondisi')
                                    ->label('Kondisi')
                                    ->maxLength(50)
                                    ->nullable(),
                            ]),
                    ]),
                Forms\Components\Section::make('Pembayaran')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('metode_bayar')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Kartu',
                                'transfer' => 'Transfer',
                                'ewallet' => 'E-Wallet',
                            ])
                            ->label('Metode Bayar')
                            ->required(),
                        Forms\Components\TextInput::make('tunai_diterima')
                            ->label('Tunai Diterima')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_penjualan')->date(),
                Tables\Columns\TextColumn::make('grand_total')->money('idr', true),
                Tables\Columns\TextColumn::make('metode_bayar'),
                Tables\Columns\TextColumn::make('tunai_diterima')->money('idr', true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kembalian')->money('idr', true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn (Penjualan $record) => route('pos.receipt', $record))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-printer'),
                Tables\Actions\DeleteAction::make()
                    ->label('Batalkan / Hapus')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Transaksi dibatalkan dan stok dikembalikan.'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New POS Transaction'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus (kembalikan stok)')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Transaksi dihapus dan stok dikembalikan.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosSales::route('/'),
            'create' => Pages\CreatePosSale::route('/create'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }
}
