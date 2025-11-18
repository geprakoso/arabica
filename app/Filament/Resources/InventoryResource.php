<?php

namespace App\Filament\Resources;

use App\Models\Produk;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\PembelianItem;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\InventoryResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;

class InventoryResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => self::applyInventoryScopes($query))
            ->defaultSort('nama_produk')
            ->columns([
                TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('brand.nama_brand')
                    ->label('Brand')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('total_qty_masuk')
                    ->label('Total Qty Masuk')
                    ->state(fn (Produk $record) => (int) ($record->total_qty_masuk ?? 0))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('total_qty_sisa')
                    ->label('Stok Tersedia')
                    ->state(fn (Produk $record) => (int) ($record->total_qty_sisa ?? 0))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('batch_count')
                    ->label('Jumlah Batch Aktif')
                    ->state(fn (Produk $record) => (int) ($record->batch_count ?? 0))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'nama_brand')
                    ->searchable(),
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'view' => Pages\ViewInventory::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfolistSection::make('Detail Produk')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama_produk')
                        ->label('Produk'),
                    TextEntry::make('brand.nama_brand')
                        ->label('Brand')
                        ->placeholder('-'),
                    TextEntry::make('kategori.nama_kategori')
                        ->label('Kategori')
                        ->placeholder('-'),
                    TextEntry::make('total_qty_masuk_display')
                        ->label('Total Qty Masuk')
                        ->state(fn (Produk $record) => self::formatNumber(self::getInventorySnapshot($record)['total_masuk'])),
                    TextEntry::make('total_qty_sisa_display')
                        ->label('Total Qty Sisa')
                        ->state(fn (Produk $record) => self::formatNumber(self::getInventorySnapshot($record)['total_sisa'])),
                    TextEntry::make('batch_count_display')
                        ->label('Jumlah Batch Aktif')
                        ->state(fn (Produk $record) => self::getInventorySnapshot($record)['batch_count'])
                        ->badge(),
                ]),
            InfolistSection::make('Batch Pembelian Aktif')
                ->schema([
                    RepeatableEntry::make('batches')
                        ->state(fn (Produk $record) => self::getInventorySnapshot($record)['batches'])
                        ->schema([
                            TextEntry::make('no_po')
                                ->label('No. PO'),
                            TextEntry::make('tanggal')
                                ->label('Tanggal Pembelian'),
                            TextEntry::make('qty_masuk')
                                ->label('Qty Masuk')
                                ->state(fn ($state) => self::formatNumber($state)),
                            TextEntry::make('qty_sisa')
                                ->label('Qty Sisa')
                                ->state(fn ($state) => self::formatNumber($state)),
                            TextEntry::make('hpp')
                                ->label('HPP / Unit')
                                ->state(fn ($state) => self::formatCurrency($state)),
                            TextEntry::make('kondisi')
                                ->label('Kondisi')
                                ->badge(),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    protected static function applyInventoryScopes(Builder $query): Builder
    {
        $produkTable = (new Produk())->getTable();
        $qtyMasukColumn = PembelianItem::qtyMasukColumn();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        $query
            ->select("{$produkTable}.*")
            ->whereHas('pembelianItems')
            ->with(['brand', 'kategori'])
            ->withSum('pembelianItems as total_qty_masuk', $qtyMasukColumn)
            ->withSum(['pembelianItems as total_qty_sisa' => fn ($q) => $q->where($qtySisaColumn, '>', 0)], $qtySisaColumn)
            ->withCount(['pembelianItems as batch_count' => fn ($q) => $q->where($qtySisaColumn, '>', 0)]);

        return $query;
    }

    protected static array $inventorySnapshotCache = [];

    protected static function getInventorySnapshot(Produk $record): array
    {
        $cacheKey = $record->getKey();

        if (isset(self::$inventorySnapshotCache[$cacheKey])) {
            return self::$inventorySnapshotCache[$cacheKey];
        }

        $qtyMasukColumn = PembelianItem::qtyMasukColumn();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        $items = $record->pembelianItems()
            ->with('pembelian')
            ->get();

        $totalMasuk = $items->sum(fn ($item) => (int) ($item->{$qtyMasukColumn} ?? 0));
        $totalSisa = $items->sum(fn ($item) => (int) ($item->{$qtySisaColumn} ?? 0));

        $activeBatches = $items
            ->filter(fn ($item) => (int) ($item->{$qtySisaColumn} ?? 0) > 0)
            ->sortBy(fn ($item) => $item->pembelian?->tanggal ?? $item->created_at)
            ->values();

        $formattedBatches = $activeBatches->map(function ($item) use ($qtyMasukColumn, $qtySisaColumn) {
            $purchase = $item->pembelian;
            $tanggal = $purchase && $purchase->tanggal
                ? $purchase->tanggal->format('d M Y')
                : '-';

            return [
                'no_po' => $purchase->no_po ?? '-',
                'tanggal' => $tanggal,
                'qty_masuk' => (int) ($item->{$qtyMasukColumn} ?? 0),
                'qty_sisa' => (int) ($item->{$qtySisaColumn} ?? 0),
                'hpp' => (int) ($item->hpp ?? 0),
                'kondisi' => ucfirst($item->kondisi ?? '-'),
            ];
        })->toArray();

        return self::$inventorySnapshotCache[$cacheKey] = [
            'total_masuk' => $totalMasuk,
            'total_sisa' => $totalSisa,
            'batch_count' => $activeBatches->count(),
            'batches' => $formattedBatches,
        ];
    }

    protected static function formatNumber($value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', '.');
    }

    protected static function formatCurrency($value): string
    {
        return 'Rp ' . number_format((int) ($value ?? 0), 0, ',', '.');
    }
}
