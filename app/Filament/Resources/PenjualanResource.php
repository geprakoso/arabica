<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Penjualan')
                    ->schema([
                        TextInput::make('no_nota')
                            ->label('No. Nota')
                            ->required()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('tanggal_penjualan')
                            ->label('Tanggal Penjualan')
                            ->required()
                            ->native(false),
                        Select::make('id_karyawan')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                        Select::make('id_member')
                            ->label('Member')
                            ->relationship('member', 'nama_member')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Produk Terjual')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label('Daftar Produk')
                            ->minItems(1)
                            ->schema([
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
                                    ->options(function (Get $get) {
                                        $productId = $get('id_produk');

                                        return self::getBatchOptions($productId ? (int) $productId : null);
                                    })
                                    ->required()
                                    ->reactive()
                                    ->disabled(fn (Get $get) => ! $get('id_produk'))
                                    ->getOptionLabelUsing(function (?int $value): ?string {
                                        if (! $value) {
                                            return null;
                                        }

                                        return self::formatBatchLabel(
                                            PembelianItem::query()->with('pembelian')->find($value),
                                            PembelianItem::qtySisaColumn(),
                                        );
                                    })
                                    ->native(false)
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        if (! $state) {
                                            return;
                                        }

                                        $batch = PembelianItem::query()
                                            ->with('pembelian')
                                            ->find($state);

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
                                    ->numeric()
                                    ->minValue(0)
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
                            ])
                            ->columns(5)
                            ->reorderable(false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items'])->withCount('items'))
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->placeholder('-')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->sortable(),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->state(fn (Penjualan $record) => $record->items->sum('qty'))
                    ->sortable(),
            ])
            ->filters([])
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'view' => Pages\ViewPenjualan::route('/{record}'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }

    protected static function getBatchOptions(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $items = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->with('pembelian')
            ->orderBy($qtyColumn, 'desc')
            ->get()
            ->mapWithKeys(fn (PembelianItem $item) => [
                $item->id_pembelian_item => self::formatBatchLabel($item, $qtyColumn),
            ]);

        return $items->all();
    }

    protected static function formatBatchLabel(?PembelianItem $item, string $qtyColumn): ?string
    {
        if (! $item) {
            return null;
        }

        $labelParts = [
            $item->pembelian?->no_po ? '#'.$item->pembelian->no_po : 'Batch '.$item->getKey(),
            'Qty: '.number_format((int) ($item->{$qtyColumn} ?? 0), 0, ',', '.'),
            'HPP: Rp '.number_format((int) ($item->hpp ?? 0), 0, ',', '.'),
        ];

        return implode(' | ', array_filter($labelParts));
    }
}
