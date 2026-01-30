<?php

namespace App\Filament\Resources\Penjadwalan\Service;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Penjadwalan\Service\CrosscheckResource\Pages;
use App\Filament\Resources\Penjadwalan\Service\CrosscheckResource\RelationManagers;
use App\Models\Crosscheck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CrosscheckResource extends BaseResource
{
    protected static ?string $model = Crosscheck::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(static::getFormSchema());
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('parent_id')
                ->label('Induk (Parent)')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload()
                ->helperText('Pilih jika ini adalah sub-item'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nama Crosscheck'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('parent.name')
                //     ->label('Induk')
                //     ->sortable()
                //     ->badge()
                //     ->color('gray')
                //     ->description(fn ($record) => $record->parent ? 'Sub-item' : 'Main Item'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Filter Induk'),
            ])
            ->groups([
                Tables\Grouping\Group::make('parent.name')
                    ->label('Parent')
                    ->collapsible(),
            ])
            ->defaultGroup('parent.name')
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(static::getFormSchema())
                    ->modalWidth('md'),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListCrosschecks::route('/'),
            'create' => Pages\CreateCrosscheck::route('/create'),
            'edit' => Pages\EditCrosscheck::route('/{record}/edit'),
        ];
    }
}
