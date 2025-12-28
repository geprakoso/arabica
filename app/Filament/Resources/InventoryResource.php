<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Produk;
use Filament\Forms\Form;
use Akaunting\Money\Money;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\PembelianItem;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Actions\StaticAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\InventoryResource\Pages;
use Filament\Tables\Columns\Layout\Grid as TableGrid;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Columns\Layout\Split as TableSplit;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\Section as InfolistSection;

class InventoryResource extends Resource
{
    protected static ?string $model = Produk::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory';
    // protected static ?string $navigationParentItem = 'Inventory & Stock' ;
    protected static ?string $navigationLabel = 'Inventory';
    protected static ?string $pluralLabel = 'Inventory';
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama_produk')
            ->columns([
                TableSplit::make([
                    ImageColumn::make('image_url')
                        ->label('')
                        ->disk('public')
                        ->height(150)
                        ->width(150)
                        ->square()
                        ->defaultImageUrl(url('/images/icons/icon-256x256.png'))
                        ->extraImgAttributes([
                            'class' => 'rounded-lg border border-gray-200/60 object-cover shadow-sm',
                            'alt' => 'Foto Produk',
                        ]),
                    Stack::make([
                        TextColumn::make('nama_produk')
                            ->description(fn(Produk $record) => 'SKU: ' . $record->sku ?? '-')
                            ->label('Produk')
                            ->formatStateUsing(fn($state) => strtoupper($state))
                            ->searchable()
                            ->weight('bold')
                            ->size(TextColumnSize::Large)
                            ->sortable()
                            ->wrap(),
                        TextColumn::make('brand.nama_brand')
                            ->label('Brand')
                            ->formatStateUsing(fn($state) => Str::title($state))
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-tag')
                            ->sortable()
                            ->columnSpan(2)
                            ->wrap(),
                        TextColumn::make('kategori.nama_kategori')
                            ->label('Kategori')
                            ->formatStateUsing(fn($state) => Str::title($state))
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-o-rectangle-stack')
                            ->sortable()
                            ->columnSpan(2)
                            ->wrap(),
                        TableGrid::make(10)->schema([
                            TextColumn::make('total_qty')
                                ->label('Stok')
                                ->state(fn(Produk $record) => (int) ($record->total_qty ?? 0))
                                ->badge()
                                ->color(fn($state) => $state > 10 ? 'success' : ($state > 3 ? 'warning' : 'danger'))
                                ->icon('heroicon-o-archive-box')
                                ->formatStateUsing(fn($state) => number_format($state ?? 0, 0, ',', '.'))
                                ->columnSpan(2)
                                ->sortable(),
                            TextColumn::make('batch_count')
                                ->label('Batch')
                                ->state(fn(Produk $record) => (int) ($record->batch_count ?? 0))
                                ->badge()
                                ->icon('heroicon-o-clipboard-document-list')
                                ->color('primary')
                                ->columnSpan(2)
                                ->toggleable(isToggledHiddenByDefault: true),

                        ]),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('latest_batch.hpp')
                            ->label('HPP')
                            ->weight('bold')
                            ->state(fn(Produk $record) => self::getInventorySnapshot($record)['latest_batch']['hpp'] ?? null)
                            ->formatStateUsing(fn($state) => is_null($state) ? '-' : self::formatCurrency($state))
                            ->alignEnd()
                            ->size(TextColumnSize::Large)
                            ->icon('heroicon-o-currency-dollar')
                            ->color('gray'),
                        TextColumn::make('latest_batch.harga_jual')
                            ->label('Harga Jual Terkini')
                            ->state(fn(Produk $record) => self::getInventorySnapshot($record)['latest_batch']['harga_jual'] ?? null)
                            ->formatStateUsing(fn($state) => is_null($state) ? '-' : self::formatCurrency($state))
                            ->weight('bold')
                            ->size(TextColumnSize::Large)
                            ->alignEnd()
                            ->icon('heroicon-o-banknotes')
                            ->color('success'),
                    ])->space(2),

                ])->from('md'),
            ])
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->relationship('brand', 'nama_brand')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('detail')
                        ->label('Detail')
                        ->slideOver()
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalWidth('6xl')
                        ->modalHeading('Detail Inventory')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
                        ->infolist(fn(Infolist $infolist) => static::infolist($infolist)),
                ])
                    ->tooltip('Aksi'),
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

    public static function getEloquentQuery(): Builder
    {
        return self::applyInventoryScopes(parent::getEloquentQuery());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Batch Pembelian Aktif')
                    ->compact()
                    ->icon('heroicon-o-archive-box')
                    ->description('Batch pembelian aktif')
                    ->schema([
                        TextEntry::make('batch_cards')
                            ->view('filament.infolists.components.inventory-batches')
                            ->state(fn(Produk $record) => self::getInventorySnapshot($record)['batches']),
                    ])
                    ->columnSpan(2),

                InfolistSection::make('Detail Produk')
                    ->compact()
                    ->icon('heroicon-o-archive-box')
                    ->schema([
                        TextEntry::make('nama_produk')
                            ->label('Produk')
                            ->extraAttributes(['class' => 'font-bold'])
                            ->formatStateUsing(fn($state) => Str::title($state))
                            ->size(TextEntrySize::Medium),
                        InfolistSection::make('')
                            ->schema([
                                TextEntry::make('brand.nama_brand')
                                    ->label('Brand')
                                    ->formatStateUsing(fn($state) => Str::title($state))
                                    ->color('gray')
                                    ->placeholder('-'),
                                TextEntry::make('kategori.nama_kategori')
                                    ->label('Kategori')
                                    ->formatStateUsing(fn($state) => Str::title($state))
                                    ->color('gray')
                                    ->placeholder('-'),
                                TextEntry::make('qty_display')
                                    ->label('Qty')
                                    ->badge()
                                    ->state(fn(Produk $record) => self::formatNumber(self::getInventorySnapshot($record)['qty'])),
                                TextEntry::make('batch_count_display')
                                    ->label(' Batch Aktif')
                                    ->state(fn(Produk $record) => self::getInventorySnapshot($record)['batch_count'])
                                    ->badge()
                                    ->color('success'),
                            ])
                            ->columns(['lg' => 2]),
                    ])->columnSpan(1),
            ])
            ->columns(3);
    }

    // Menerapkan scope khusus untuk menampilkan hanya produk dengan inventory aktif

    protected static function applyInventoryScopes(Builder $query): Builder
    {
        $produkTable = (new Produk())->getTable();
        $qtySisaColumn = PembelianItem::qtySisaColumn();

        $query
            ->select("{$produkTable}.*")
            ->whereHas('pembelianItems', fn($q) => $q->where($qtySisaColumn, '>', 0))
            ->with([
                'brand',
                'kategori',
                'pembelianItems' => fn($q) => $q->with('pembelian'),
            ])
            ->withSum(['pembelianItems as total_qty' => fn($q) => $q->where($qtySisaColumn, '>', 0)], $qtySisaColumn)
            ->withCount(['pembelianItems as batch_count' => fn($q) => $q->where($qtySisaColumn, '>', 0)]);

        return $query;
    }

    protected static array $inventorySnapshotCache = [];

    /**
     * Mendapatkan snapshot inventory untuk produk tertentu.
     *
     * Snapshot meliputi:
     * - total qty saat ini
     * - jumlah batch aktif
     * - detail setiap batch (no_po, tanggal, qty, hpp, harga_jual, kondisi)
     * - informasi batch terbaru (hpp, harga_jual, tanggal)
     *
     * Hasil disimpan dalam cache statis agar tidak perlu menghitung ulang
     * dalam satu siklus request yang sama.
     *
     * @param  \App\Models\Produk  $record
     * @return array{
     *     qty: int,
     *     batch_count: int,
     *     batches: array<int, array<string, mixed>>,
     *     latest_batch: array<string, mixed>|null
     * }
     */
    protected static function getInventorySnapshot(Produk $record): array
    {
        $cacheKey = $record->getKey();

        if (isset(self::$inventorySnapshotCache[$cacheKey])) {
            return self::$inventorySnapshotCache[$cacheKey];
        }

        $qtySisaColumn = PembelianItem::qtySisaColumn();

        if ($record->relationLoaded('pembelianItems')) {
            $items = $record->pembelianItems;
            $items->loadMissing('pembelian');
        } else {
            $items = $record->pembelianItems()
                ->with('pembelian')
                ->get();
        }

        $totalQty = $items->sum(fn($item) => (int) ($item->{$qtySisaColumn} ?? 0));

        $activeBatches = $items
            ->filter(fn($item) => (int) ($item->{$qtySisaColumn} ?? 0) > 0)
            ->sortBy(fn($item) => $item->pembelian?->tanggal ?? $item->created_at)
            ->values();

        $formattedBatches = $activeBatches->map(function ($item) use ($qtySisaColumn) {
            $purchase = $item->pembelian;
            $tanggal = $purchase && $purchase->tanggal
                ? $purchase->tanggal->format('d M Y')
                : '-';
            $rawHpp = $item->hpp;

            $rawHargaJual = $item->harga_jual;
            $hpp = is_null($rawHpp) ? null : (int) $rawHpp;
            $hargaJual = is_null($rawHargaJual) ? null : (int) $rawHargaJual;
            return [
                'no_po' => $purchase->no_po ?? '-',
                'tanggal' => $tanggal,
                'qty' => (int) ($item->{$qtySisaColumn} ?? 0),
                'hpp' => $hpp,
                'harga_jual' => $hargaJual,
                'hpp_display' => is_null($hpp) ? null : self::formatCurrency($hpp),
                'harga_jual_display' => is_null($hargaJual) ? null : self::formatCurrency($hargaJual),
                'kondisi' => ucfirst($item->kondisi ?? '-'),
            ];
        })->toArray();

        $latestBatchRecord = $activeBatches->last();
        $latestBatch = null;

        if ($latestBatchRecord) {
            $latestHpp = $latestBatchRecord->hpp;
            $latestHargaJual = $latestBatchRecord->harga_jual;

            $latestBatch = [
                'hpp' => is_null($latestHpp) ? null : (int) $latestHpp,
                'harga_jual' => is_null($latestHargaJual) ? null : (int) $latestHargaJual,
                'tanggal' => optional($latestBatchRecord->pembelian?->tanggal)->format('d M Y'),
            ];
        }

        return self::$inventorySnapshotCache[$cacheKey] = [
            'qty' => $totalQty,
            'batch_count' => $activeBatches->count(),
            'batches' => $formattedBatches,
            'latest_batch' => $latestBatch,
        ];
    }

    // Helper untuk format ringkasan batch dalam bentuk string.
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

    // Helper untuk format angka dengan pemisah ribuan.
    protected static function formatNumber($value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', '.');
    }

    // Helper untuk format mata uang IDR.
    protected static function formatCurrency($value): string
    {
        $amount = (int) ($value ?? 0);

        return Money::IDR($amount, true)->formatWithoutZeroes();
    }
}
