<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestOrderResource\Pages;
use App\Models\RequestOrder;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RequestOrderResource extends Resource
{
    protected static ?string $model = RequestOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Request Order';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Permintaan')
                    ->schema([
                        Forms\Components\TextInput::make('no_ro')
                            ->label('No. RO')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->native(false),
                        Forms\Components\RichEditor::make('catatan')
                            ->label('Catatan')
                            ->nullable(),
                    ])->columns(2),

                Section::make('Daftar Produk')
                    ->schema([
                        Repeater::make('items')
                            ->label('Produk Diminta')
                            ->relationship('items')
                            ->minItems(1)
                            ->schema([
                                Forms\Components\Select::make('produk_id')
                                    ->label('Produk')
                                    ->relationship('produk', 'nama_produk' , )
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                            ])
                            ->reorderable(false)
                            ->columns(1),
                    ])->collapsed(false),
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
