<?php

namespace App\Filament\Resources\PenjualanResource\RelationManagers;

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

    protected static ?string $title = 'Produk Terjual';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('id_produk')
                ->label('Produk')
                ->relationship('produk', 'nama_produk')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->native(false)
                ->afterStateUpdated(function (Set $set): void {
                    $set('id_pembelian_item', null);
                }),
            Select::make('id_pembelian_item')
                ->label('Batch')
                ->required()
                ->reactive()
                ->native(false)
                ->options(function (Get $get) {
                    $productId = $get('id_produk');

                    return PenjualanResource::getBatchOptions($productId ? (int) $productId : null);
                })
                ->disabled(fn (Get $get) => ! $get('id_produk'))
                ->getOptionLabelUsing(function (?int $value): ?string {
                    if (! $value) {
                        return null;
                    }

                    $batch = PembelianItem::query()
                        ->with('pembelian')
                        ->find($value);

                    return PenjualanResource::formatBatchLabel($batch, PembelianItem::qtySisaColumn());
                })
                ->afterStateUpdated(function (Set $set, ?int $state): void {
                    if (! $state) {
                        return;
                    }

                    $batch = PembelianItem::query()->find($state);

                    if (! $batch) {
                        return;
                    }

                    $set('hpp', $batch->hpp);
                    $set('harga_jual', $batch->harga_jual);
                    $set('kondisi', $batch->kondisi);
                }),
            TextInput::make('qty')
                ->label('Qty')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('hpp')
                ->label('HPP')
                ->disabled()
                ->numeric()
                ->minValue(0)
                ->helperText('Otomatis mengikuti batch terpilih.')
                ->required(),
            TextInput::make('harga_jual')
                ->label('Harga Jual')
                ->numeric()
                ->minValue(0)
                ->required(),
            Select::make('kondisi')
                ->label('Kondisi')
                ->options([
                    'baru' => 'Baru',
                    'bekas' => 'Bekas',
                ])
                ->default('baru')
                ->required()
                ->native(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('produk.nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('pembelianItem.pembelian.no_po')
                    ->label('No. PO')
                    ->placeholder('-'),
                TextColumn::make('pembelianItem.pembelian.tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('-'),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric(),
                TextColumn::make('hpp')
                    ->label('HPP')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.')),
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.')),
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Produk'),
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
