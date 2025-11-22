<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use App\Filament\Resources\PenjualanResource\RelationManagers\ItemsRelationManager;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
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
                            ->default(fn () => Penjualan::generateNoNota())
                            ->disabled()
                            ->unique(ignoreRecord: true)
                            ->required(),
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
                        RichEditor::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
            ItemsRelationManager::class,
        ];
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

    public static function getBatchOptions(?int $productId): array
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

    public static function formatBatchLabel(?PembelianItem $item, string $qtyColumn): ?string
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

    public static function getAvailableProductOptions(): array
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return Produk::query()
            ->whereHas('pembelianItems', fn (Builder $query) => $query->where($qtyColumn, '>', 0))
            ->orderBy('nama_produk')
            ->pluck('nama_produk', 'id')
            ->all();
    }
}
