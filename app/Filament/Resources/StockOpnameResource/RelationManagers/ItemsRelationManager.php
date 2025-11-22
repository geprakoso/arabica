<?php

namespace App\Filament\Resources\StockOpnameResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Detail Opname';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('produk_id')
                ->label('Produk')
                ->options(fn () => PenjualanResource::getAvailableProductOptions())
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->native(false)
                ->afterStateUpdated(fn (Set $set) => $set('pembelian_item_id', null)),
            Select::make('pembelian_item_id')
                ->label('Batch')
                ->options(fn (Get $get) => PenjualanResource::getBatchOptions($get('produk_id') ? (int) $get('produk_id') : null))
                ->required()
                ->native(false)
                ->disabled(fn (Get $get) => ! $get('produk_id'))
                ->reactive()
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    if (! $state) {
                        return;
                    }

                    $batch = PembelianItem::query()->find($state);

                    if (! $batch) {
                        return;
                    }

                    $qtyColumn = PembelianItem::qtySisaColumn();
                    $set('stok_sistem', (int) ($batch->{$qtyColumn} ?? 0));
                }),
            TextInput::make('stok_sistem')
                ->label('Stok Sistem')
                ->numeric()
                ->required()
                ->disabled()
                ->dehydrated(),
            TextInput::make('stok_fisik')
                ->label('Stok Fisik')
                ->numeric()
                ->required()
                ->live()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $selisih = ((int) $state) - (int) $get('stok_sistem');
                    $set('selisih', $selisih);
                }),
            TextInput::make('selisih')
                ->label('Selisih')
                ->numeric()
                ->disabled()
                ->dehydrated(),
            TextInput::make('catatan')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('produk.nama_produk')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('pembelianItem.pembelian.no_po')
                    ->label('No. PO')
                    ->placeholder('-'),
                TextColumn::make('stok_sistem')
                    ->label('Sistem'),
                TextColumn::make('stok_fisik')
                    ->label('Fisik'),
                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state > 0,
                        'danger' => fn ($state) => $state < 0,
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Item')
                    ->visible(fn () => ! $this->getOwnerRecord()->isPosted()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => ! $record->opname->isPosted()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => ! $record->opname->isPosted()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => ! $this->getOwnerRecord()->isPosted()),
            ]);
    }
}
