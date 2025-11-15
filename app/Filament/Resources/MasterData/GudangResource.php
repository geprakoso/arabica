<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\GudangResource\Pages;
// use App\Filament\Resources\MasterData\GudangResource\RelationManagers;
use App\Models\Gudang;
// use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Filament\Tables\Actions;
// use Filament\Tables\Columns;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;

class GudangResource extends Resource
{
    protected static ?string $model = Gudang::class;

    protected static ?string $navigationIcon = 'hugeicons-warehouse';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Gudang';
    protected static ?int $navigationSort = 7;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Data Gudang')
                    ->schema([
                        TextInput::make('nama_gudang')
                            ->label('Nama Gudang')
                            ->required(),
                        TextInput::make('lokasi_gudang')
                            ->label('Lokasi Gudang')
                            ->required(),
                    ]),
                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktifkan Gudang')
                            ->default(true),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_gudang')
                    ->label('Nama Gudang')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('lokasi_gudang')
                    ->label('Lokasi')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktifkan')
                    ->boolean(),
            ])
            ->filters([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGudangs::route('/'),
            'create' => Pages\CreateGudang::route('/create'),
            'view' => Pages\ViewGudang::route('/{record}'),
            'edit' => Pages\EditGudang::route('/{record}/edit'),
        ];
    }
}
