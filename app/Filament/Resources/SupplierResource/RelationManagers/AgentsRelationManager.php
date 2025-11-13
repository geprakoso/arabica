<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_agen')
                    ->required()
                    ->maxLength(255),
                TextInput::make('no_hp_agen')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_agen')
            ->columns([
                Tables\Columns\TextColumn::make('nama_agen')
                    ->label('Nama Agen')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_hp_agen')
                    ->label('No. HP Agen')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Is Active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
