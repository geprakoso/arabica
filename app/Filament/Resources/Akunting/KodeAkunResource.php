<?php

namespace App\Filament\Resources\Akunting;

use App\Enums\KategoriAkun; // Sesuaikan namespace Enum Anda
use App\Filament\Resources\Akunting\KodeAkunResource\Pages;
use App\Models\KodeAkun; // Sesuaikan namespace Model Anda
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class KodeAkunResource extends Resource
{
    protected static ?string $model = KodeAkun::class;
    protected static ?string $navigationLabel = 'Kode Akun';
    protected static ?string $pluralLabel = 'Kode Akun';
    protected static ?string $navigationIcon = 'hugeicons-bar-code-02';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Akun')
                    ->schema([
                        TextInput::make('kode_akun')
                            ->label('Kode Akun')
                            ->required()
                            ->maxLength(255)
                            ->unique(KodeAkun::class, 'kode_akun', ignoreRecord: true),

                        TextInput::make('nama_akun')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255),

                        Select::make('kategori_akun')
                            ->label('Kategori Akun')
                            ->options(KategoriAkun::class)
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kategori')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_akun')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_akun')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori_akun')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => KategoriAkun::tryFrom($state)?->getLabel() ?? '-')
                    ->color(fn (?string $state) => KategoriAkun::tryFrom($state)?->getColor()),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListKodeAkuns::route('/'),
            'create' => Pages\CreateKodeAkun::route('/create'),
            'view' => Pages\ViewKodeAkun::route('/{record}'),
            'edit' => Pages\EditKodeAkun::route('/{record}/edit'),
        ];
    }
}
