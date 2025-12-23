<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RequestOrder;
use Filament\Resources\Resource;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\RequestOrderResource\Pages;

class RequestOrderResource extends Resource
{
    protected static ?string $model = RequestOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $recordTitleAttribute = 'no_ro';

    protected static ?string $pluralLabel = 'Request Order';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make('Informasi Utama')
                        ->icon('heroicon-o-document-text')
                        ->description('Detail permintaan barang baru')
                        ->schema([
                            Forms\Components\TextInput::make('no_ro')
                                ->label('No. RO')
                                ->prefixIcon('heroicon-o-hashtag')
                                ->required()
                                ->default(fn() => RequestOrder::generateRO())
                                ->disabled()
                                ->dehydrated(true)
                                ->unique(ignoreRecord: true),
                            Forms\Components\Select::make('karyawan_id')
                                ->label('Karyawan Pemohon')
                                ->prefixIcon('heroicon-o-user')
                                ->relationship('karyawan', 'nama_karyawan')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->placeholder('Pilih Nama Karyawan')
                                ->required(),
                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Permintaan')
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->displayFormat('d F Y')
                                ->required()
                                ->default(now())
                                ->native(false),
                        ]),
                    Section::make('Keterangan Tambahan')
                        ->icon('heroicon-o-pencil')
                        ->description('Catatan opsional untuk permintaan ini')
                        ->schema([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan')
                                ->rows(5)
                                ->placeholder('Tuliskan alasan atau catatan penting lainnya...')
                                ->columnSpanFull()
                                ->nullable(),
                        ])
                ])
                    ->columnSpanFull(),

                Section::make('Daftar Item')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Masukkan daftar barang yang ingin diminta')
                    ->schema([
                        TableRepeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->minItems(1)
                            ->addActionLabel('Tambah Produk Lain')
                            ->schema([
                                Forms\Components\Select::make('produk_id')
                                    ->label('Nama Produk')
                                    ->prefixIcon('heroicon-o-cube')
                                    ->relationship('produk', 'nama_produk')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->placeholder('Cari Produk...')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('kategori_nama', null);
                                            $set('brand_nama', null);
                                            return;
                                        }
                                        $product = \App\Models\Produk::with(['kategori', 'brand'])->find($state);
                                        $set('kategori_nama', $product?->kategori?->nama_kategori ?? '-');
                                        $set('brand_nama', $product?->brand?->nama_brand ?? '-');
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('kategori_nama')
                                    ->label('Kategori')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-tag'),
                                Forms\Components\TextInput::make('brand_nama')
                                    ->label('Brand')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-star'),
                            ])
                            ->reorderable(true)
                            ->columns(4)
                            ->cloneable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ro')
                    ->label('No. RO')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag')
                    ->color('primary')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Pemohon')
                    ->icon('heroicon-o-user-circle')
                    ->weight('medium')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->badge()
                    ->icon('heroicon-o-shopping-cart')
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('karyawan_id')
                    ->label('Filter Karyawan')
                    ->native(false)
                    ->relationship('karyawan', 'nama_karyawan'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->link()
                    ->label('Aksi'),
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
            'index' => Pages\ListRequestOrders::route('/'),
            'create' => Pages\CreateRequestOrder::route('/create'),
            'view' => Pages\ViewRequestOrder::route('/{record}'),
            'edit' => Pages\EditRequestOrder::route('/{record}/edit'),
        ];
    }
}
