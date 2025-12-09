<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InputTransaksiTokoResource\Pages;
use App\Filament\Resources\InputTransaksiTokoResource\RelationManagers;
use App\Models\InputTransaksiToko;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InputTransaksiTokoResource extends Resource
{
    protected static ?string $model = InputTransaksiToko::class;

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
            'index' => Pages\ListInputTransaksiTokos::route('/'),
            'create' => Pages\CreateInputTransaksiToko::route('/create'),
            'view' => Pages\ViewInputTransaksiToko::route('/{record}'),
            'edit' => Pages\EditInputTransaksiToko::route('/{record}/edit'),
        ];
    }
}
