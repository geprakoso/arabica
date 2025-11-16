<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AkunTransaksiResource\Pages;
use App\Filament\Resources\AkunTransaksiResource\RelationManagers;
use App\Models\AkunTransaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AkunTransaksiResource extends Resource
{
    protected static ?string $model = AkunTransaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListAkunTransaksis::route('/'),
            'create' => Pages\CreateAkunTransaksi::route('/create'),
            'view' => Pages\ViewAkunTransaksi::route('/{record}'),
            'edit' => Pages\EditAkunTransaksi::route('/{record}/edit'),
        ];
    }
}
