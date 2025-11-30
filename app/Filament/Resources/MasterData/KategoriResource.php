<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\KategoriResource\Pages;
// use App\Filament\Resources\MasterData\KategoriResource\RelationManagers;
use App\Models\Kategori;
use Filament\Tables\Columns\TextColumn;
// use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Ramsey\Uuid\Guid\Fields;

class KategoriResource extends Resource
{
    protected static ?string $model = Kategori::class;
    // protected static ?string $cluster = MasterData::class;
    // protected static ?string $navigationIcon = 'hugeicons-tags';
    // protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'Master Data';
    protected static ?string $pluralModelLabel = 'Kategori';
    protected static ?string $navigationLabel = 'Kategori';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Detail Kategori')
                    ->schema([
                        Forms\Components\TextInput::make('nama_kategori')
                            ->label('Nama Kategori')
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->unique(ignoreRecord: true),
                    ]),

                // Section::make('Status')
                //     ->schema([
                //         Toggle::make('is_active')
                //             ->label('Aktifkan Kategori')
                //             ->default(true)
                //             ->hidden()
                //             ->required(),
                //     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_kategori')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('is_active')
                //     ->label('Aktif')
                //     ->badge()
                //     ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Nonaktif')
                //     ->color(fn (bool $state) => $state ? 'success' : 'danger')
                //     ->sortable(),
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
            'index' => Pages\ListKategoris::route('/'),
            'create' => Pages\CreateKategori::route('/create'),
            'view' => Pages\ViewKategori::route('/{record}'),
            'edit' => Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}
