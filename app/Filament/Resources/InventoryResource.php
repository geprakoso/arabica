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
use App\Filament\Resources\InventoryResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\ViewEntry;

use function Laravel\Prompts\form;

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
                TextColumn::make('total_qty')
                    ->label('Qty')
                    ->state(fn (Produk $record) => (int) ($record->total_qty ?? 0))
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('latest_batch.hpp')
                    ->label('HPP Terbaru')
                    ->state(fn (Produk $record) => self::getInventorySnapshot($record)['latest_batch']['hpp'] ?? 0)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('latest_batch.harga_jual')
                    ->label('Harga Jual Terbaru')
                    ->state(fn (Produk $record) => self::getInventorySnapshot($record)['latest_batch']['harga_jual'] ?? 0)
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
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
                    TextEntry::make('qty_display')
                        ->label('Qty')
                        ->state(fn (Produk $record) => self::formatNumber(self::getInventorySnapshot($record)['qty'])),
                    TextEntry::make('batch_count_display')
                        ->label('Jumlah Batch Aktif')
                        ->state(fn (Produk $record) => self::getInventorySnapshot($record)['batch_count'])
                        ->badge(),
                ]),
            InfolistSection::make('Batch Pembelian Aktif')
                ->schema([
                    ViewEntry::make('batch_cards')
                        ->view('filament.infolists.components.inventory-batches')
                        ->state(fn (Produk $record) => self::getInventorySnapshot($record)['batches'])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected static function applyInventoryScopes(Builder $query): Builder
    {
        $produkTable = (new Produk())->getTable();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        $query
            ->select("{$produkTable}.*")
            ->whereHas('pembelianItems')
            ->with(['brand', 'kategori'])
            ->withSum(['pembelianItems as total_qty' => fn ($q) => $q->where($qtySisaColumn, '>', 0)], $qtySisaColumn)
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

        $qtySisaColumn = PembelianItem::qtySisaColumn();

        $items = $record->pembelianItems()
            ->with('pembelian')
            ->get();

        $totalQty = $items->sum(fn ($item) => (int) ($item->{$qtySisaColumn} ?? 0));

        $activeBatches = $items
            ->filter(fn ($item) => (int) ($item->{$qtySisaColumn} ?? 0) > 0)
            ->sortBy(fn ($item) => $item->pembelian?->tanggal ?? $item->created_at)
            ->values();

        $formattedBatches = $activeBatches->map(function ($item) use ($qtySisaColumn) {
            $purchase = $item->pembelian;
            $tanggal = $purchase && $purchase->tanggal
                ? $purchase->tanggal->format('d M Y')
                : '-';

            return [
                'no_po' => $purchase->no_po ?? '-',
                'tanggal' => $tanggal,
                'qty' => (int) ($item->{$qtySisaColumn} ?? 0),
                'hpp' => (int) ($item->hpp ?? 0),
                'harga_jual' => (int) ($item->harga_jual ?? 0),
                'kondisi' => ucfirst($item->kondisi ?? '-'),
            ];
        })->toArray();

        $latestBatchRecord = $activeBatches->last();
        $latestBatch = $latestBatchRecord
            ? [
                'hpp' => (int) ($latestBatchRecord->hpp ?? 0),
                'harga_jual' => (int) ($latestBatchRecord->harga_jual ?? 0),
                'tanggal' => optional($latestBatchRecord->pembelian?->tanggal)->format('d M Y'),
            ]
            : null;

        return self::$inventorySnapshotCache[$cacheKey] = [
            'qty' => $totalQty,
            'batch_count' => $activeBatches->count(),
            'batches' => $formattedBatches,
            'latest_batch' => $latestBatch,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $batches
     * @return array<int, string>
     */
    protected static function formatBatchSummaries(array $batches): array
    {
        if (! count($batches)) {
            return [];
        }

        $summaries = [];

        foreach ($batches as $index => $batch) {
            $label = trim((string) ($batch['no_po'] ?? ''));
            $label = $label !== '' && $label !== '-' ? $label : 'Batch ' . ($index + 1);

            if (! empty($batch['tanggal']) && $batch['tanggal'] !== '-') {
                $label .= " ({$batch['tanggal']})";
            }

            $segments = [
                $label,
                'Qty: ' . self::formatNumber($batch['qty'] ?? 0),
            ];

            if (! empty($batch['hpp'])) {
                $segments[] = 'HPP: ' . self::formatCurrency($batch['hpp']);
            }

            if (! empty($batch['harga_jual'])) {
                $segments[] = 'Harga: ' . self::formatCurrency($batch['harga_jual']);
            }

            if (! empty($batch['kondisi']) && $batch['kondisi'] !== '-') {
                $segments[] = 'Kondisi: ' . $batch['kondisi'];
            }

            $summaries[] = implode(' · ', $segments);
        }

        return $summaries;
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
