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

    protected static ?string $navigationLabel = 'Request Order';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make('Detail Permintaan')
                        ->schema([
                            Forms\Components\TextInput::make('no_ro')
                                ->label('No. RO')
                                ->required()
                                ->default(fn () => RequestOrder::generateRO())
                                ->disabled()
                                ->unique(ignoreRecord: true),
                            Forms\Components\Select::make('karyawan_id')
                                ->label('Karyawan')
                                ->relationship('karyawan', 'nama_karyawan')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->required(),
                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->native(false),
                        ]),
                    Section::make('Catatan')
                        ->schema([
                            Forms\Components\RichEditor::make('catatan')
                                ->label('Catatan')
                                ->columnSpanFull()
                                ->nullable(),
                        ])
                        ]) ->columnSpanFull(),

                Section::make('Daftar Produk')
                    ->schema([
                    TableRepeater::make('items')
                        ->label('Produk Diminta')
                        ->relationship('items')
                        ->minItems(1)
                        ->schema([
                            Forms\Components\Select::make('produk_id')
                                ->label('Produk')
                                ->relationship('produk', 'nama_produk')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        $set('kategori_nama', null);
                                        $set('brand_nama', null);
                                        return;
                                    }
                                    $product = \App\Models\Produk::with(['kategori', 'brand'])->find($state);
                                    $set('kategori_nama', $product?->kategori?->nama_kategori ?? null);
                                    $set('brand_nama', $product?->brand?->nama_brand ?? null);
                                }),
                                Forms\Components\TextInput::make('kategori_nama')
                                    ->label('Kategori')
                                    ->disabled(),
                                Forms\Components\TextInput::make('brand_nama')
                                    ->label('Brand')
                                    ->disabled(),
                            ])
                            ->reorderable(false)
                            ->columns(3)
                            ->cloneable(),
                    ])
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ro')
                    ->label('No. RO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->relationship('karyawan', 'nama_karyawan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
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
