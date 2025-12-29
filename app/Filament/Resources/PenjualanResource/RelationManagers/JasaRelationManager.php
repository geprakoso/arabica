<?php

namespace App\Filament\Resources\PenjualanResource\RelationManagers;

use App\Models\Jasa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class JasaRelationManager extends RelationManager
{
    protected static string $relationship = 'jasaItems';

    protected static ?string $title = 'Jasa Terjual';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('jasa_id')
                ->label('Jasa')
                ->relationship('jasa', 'nama_jasa')
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    if (! $state) {
                        return;
                    }

                    $hargaDefault = Jasa::query()->find($state)?->harga;

                    if ($hargaDefault !== null) {
                        $set('harga', $hargaDefault);
                    }
                }),
            Forms\Components\TextInput::make('harga')
                ->label('Tarif Jasa')
                ->numeric()
                ->minValue(0)
                ->prefix('Rp')
                ->currencyMask(
                    thousandSeparator: '.',
                    decimalSeparator: ',',
                    precision: 0,
                )
                ->required(),
            Forms\Components\Textarea::make('catatan')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jasa.nama_jasa')
                    ->label('Jasa')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('harga')
                    ->label('Tarif')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.')),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(40)
                    ->wrap(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Jasa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
