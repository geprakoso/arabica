<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Filament\Tables\Table;
use App\Models\RequestOrder;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PembelianResource\Pages;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Pembelian';

    protected static ?string $pluralLabel = 'Pembelian';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Pembelian')
                    ->tabs([
                        Tab::make('Detail Pembelian')
                            ->schema([
                                TextInput::make('no_po')
                                    ->label('No. PO')
                                    ->prefixIcon('heroicon-s-tag')
                                    ->required()
                                    ->default(fn() => Pembelian::generatePO()) //generate no_po otomatis
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->unique(ignoreRecord: true),
                                DatePicker::make('tanggal')
                                    ->label('Tanggal Pembelian')
                                    ->required()
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->placeholder('default: hari ini')
                                    ->prefixIcon('heroicon-s-calendar')
                                    ->native(false),
                                Select::make('id_karyawan')
                                    ->label('Karyawan')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false),
                                Select::make('id_supplier')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'nama_supplier')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false),
                                Select::make('requestOrders')
                                    ->label('Request Order')
                                    ->relationship('requestOrders', 'no_ro')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->helperText('Dapat memilih lebih dari satu Request Order; catatan otomatis diisi tag nomor RO.')
                                    ->afterStateUpdated(function (callable $set, ?array $state): void {
                                        $set('catatan', self::formatRequestOrderReferences($state ?? []));
                                    }), // memperbarui catatan saat request order diubah
                                Select::make('tipe_pembelian')
                                    ->label('Tipe Pembelian')
                                    ->options([
                                        'ppn' => 'PPN',
                                        'non_ppn' => 'Non PPN',
                                    ])
                                    ->required()
                                    ->default('non_ppn')
                                    ->native(false),
                                Select::make('jenis_pembayaran')
                                    ->label('Jenis Pembayaran')
                                    ->options([
                                        'lunas' => 'Lunas',
                                        'tempo' => 'Tempo',
                                    ])
                                    ->required()
                                    ->default('lunas')
                                    ->live()
                                    ->native(false)
                                    ->afterStateUpdated(function (callable $set, ?string $state): void {
                                        if ($state !== 'tempo') {
                                            $set('tgl_tempo', null);
                                        }
                                    }),
                                DatePicker::make('tgl_tempo')
                                    ->label('Tanggal Tempo')
                                    ->native(false)
                                    ->visible(fn(callable $get) => $get('jenis_pembayaran') === 'tempo')
                                    ->required(fn(callable $get) => $get('jenis_pembayaran') === 'tempo'),
                            ])
                            ->columns(2),
                        Tab::make('Produk Dibeli')
                            ->schema([
                                TableRepeater::make('items')
                                    ->relationship('items')
                                    ->label('Daftar Produk')
                                    ->minItems(1)
                                    ->schema([
                                        Select::make('id_produk')
                                            ->label('Produk')
                                            ->relationship('produk', 'nama_produk')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),
                                        TextInput::make('hpp')
                                            ->label('HPP')
                                            ->numeric()
                                            ->prefix('Rp ')
                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2) // format pemisah uang
                                            ->minValue(0)
                                            ->required(),
                                        TextInput::make('harga_jual')
                                            ->label('Harga Jual')
                                            ->numeric()
                                            ->prefix('Rp ')
                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2) // format pemisah uang
                                            ->minValue(0)
                                            ->required(),
                                        TextInput::make('qty')
                                            ->label('Qty')
                                            ->numeric()
                                            ->minValue(1)
                                            ->required(),
                                        Select::make('kondisi')
                                            ->label('Kondisi')
                                            ->options([
                                                'baru' => 'Baru',
                                                'bekas' => 'Bekas',
                                            ])
                                            ->default('baru')
                                            ->required()
                                            ->native(false),
                                    ])
                                    ->colStyles([
                                        'hpp' => 'width: 180px;',
                                        'harga_jual' => 'width: 180px;',
                                        'qty' => 'width: 80px;',
                                        'kondisi' => 'width: 150px;',
                                    ]) // format kolom size dan alignment
                                    ->columns(5)
                                    ->cloneable()
                                    ->reorderable(false),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('requestOrders'))
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('request_orders_label')
                    ->label('Request Order')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-hashtag')
                    ->state(fn(Pembelian $record) => $record->requestOrders
                        ->map(fn($ro) => '#' . $ro->no_ro)
                        ->toArray())
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('tipe_pembelian')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'ppn',
                        'gray' => 'non_ppn',
                    ]),
                TextColumn::make('jenis_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'lunas',
                        'danger' => 'tempo',
                    ]),
                TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->icon('heroicon-m-shopping-cart')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('primary')
                        ->tooltip('Lihat Detail'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->tooltip('Edit'),
                ])
                    ->label('Aksi')
                    ->tooltip('Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'view' => Pages\ViewPembelian::route('/{record}'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }

    // mengubah array id request order jadi teks tag seperti #RO123, #RO124
    protected static function formatRequestOrderReferences(array $requestOrderIds): ?string
    {
        $ids = collect($requestOrderIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return null;
        }

        $tags = RequestOrder::query()
            ->whereIn('id', $ids)
            ->pluck('no_ro')
            ->filter()
            ->map(fn($noRo) => "#{$noRo}")
            ->toArray();

        return empty($tags) ? null : implode(', ', $tags);
    }
}
