<?php

namespace App\Filament\Resources\MasterData;

use Filament\Forms;
// use App\Filament\Resources\MasterData\KategoriResource\RelationManagers;
use Filament\Tables;
use App\Models\Kategori;
// use Dom\Text;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\MasterData\KategoriResource\Pages;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Ramsey\Uuid\Guid\Fields;

class KategoriResource extends BaseResource
{
    protected static ?string $model = Kategori::class;
    // protected static ?string $cluster = MasterData::class;
    // protected static ?string $navigationIcon = 'hugeicons-tags';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'Produk & Kategori';
    protected static ?string $pluralModelLabel = 'Kategori';
    protected static ?string $navigationLabel = 'Kategori';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Detail Kategori')
                    ->schema([
                        Forms\Components\TextInput::make('nama_kategori')
                            ->label('Nama Kategori')
                            ->dehydrateStateUsing(fn($state) => Str::title($state))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('slug', Str::slug($state));
                            })
                            ->rules([
                                fn (\Filament\Forms\Get $get, ?Kategori $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $slug = Str::slug($value);
                                    $query = Kategori::where('slug', $slug);
                                    
                                    if ($record) {
                                        $query->where('id', '!=', $record->id);
                                    }
                                    
                                    if ($query->exists()) {
                                        $fail('Kategori dengan nama ini sudah ada. Silakan gunakan nama yang berbeda.');
                                    }
                                },
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->dehydrateStateUsing(fn($state) => Str::slug($state))
                            ->live(onBlur: true),
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
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->label('Kategori')
                    ->weight('bold')
                    ->icon('heroicon-o-tag')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->icon('heroicon-m-link')
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\EditAction::make()->color('warning'),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
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
