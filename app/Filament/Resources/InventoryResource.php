<?php

namespace App\Filament\Resources;

use Akaunting\Money\Money;
use App\Filament\Actions\SummaryExportHeaderAction;
use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Kategori;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Models\StockBatch;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Grid as TableGrid;
use Filament\Tables\Columns\Layout\Split as TableSplit;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InventoryResource extends BaseResource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Stock Ready';

    protected static ?string $pluralLabel = 'Stock Ready';

    protected static ?string $modelLabel = 'Inventory';

    protected static ?string $pluralModelLabel = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_produk', 'sku', 'brand.nama_brand', 'kategori.nama_kategori'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                            ->description(fn(Produk $record) => new HtmlString(
                                '<span class="font-mono">SKU: ' . e($record->sku ?? '-') . '</span>' .
                                ($record->deleted_at ? ' <span class="text-danger-500 text-xs">[DELETED]</span>' : '')
                            ))
                            ->label('Produk')
                            ->formatStateUsing(fn($state, Produk $record) => Str::upper($state) . ($record->deleted_at ? ' ⚠️' : ''))
                            ->searchable()
                            ->weight('bold')
                            ->size(TextColumnSize::Large)
                            ->sortable()
                            ->wrap()
                            ->color(fn(Produk $record) => $record->deleted_at ? 'danger' : null),
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
                                ->color(fn($state) => match (true) {
                                    $state <= 0 => 'danger',
                                    $state <= 3 => 'warning',
                                    $state <= 10 => 'primary',
                                    default => 'success',
                                })
                                ->icon(fn($state) => match (true) {
                                    $state <= 0 => 'heroicon-o-x-circle',
                                    $state <= 3 => 'heroicon-o-exclamation-triangle',
                                    default => 'heroicon-o-archive-box',
                                })
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
                SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'ready' => 'Ready (> 10)',
                        'low' => 'Low Stock (1-10)',
                        'out' => 'Out of Stock (0)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'ready' => $query->having('total_qty', '>', 10),
                            'low' => $query->havingRaw('total_qty > 0 AND total_qty <= 10'),
                            'out' => $query->having('total_qty', '<=', 0),
                            default => $query,
                        };
                    }),
                Filter::make('show_deleted')
                    ->label('Tampilkan Produk Terhapus')
                    ->form([
                        Toggle::make('value')
                            ->label('Tampilkan produk yang sudah dihapus')
                            ->default(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! ($data['value'] ?? false)) {
                            return $query->whereNull('deleted_at');
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                SummaryExportHeaderAction::make('export_inventory_pdf')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->fileName('Stok Opname ' . '_' . date('d M Y'))
                    ->defaultFormat('pdf')
                    ->disableTableColumns()
                    ->modalHeading(false)
                    ->withColumns([
                        TextColumn::make('sku')
                            ->label('SKU'),
                        TextColumn::make('nama_produk')
                            ->label('Nama Produk'),
                        TextColumn::make('brand.nama_brand')
                            ->label('Brand'),
                        TextColumn::make('kategori.nama_kategori')
                            ->label('Kategori'),
                        TextColumn::make('total_qty')
                            ->label('Stok Sistem')
                            ->state(fn(Produk $record) => (int) ($record->total_qty ?? 0)),
                        TextColumn::make('latest_batch.hpp')
                            ->label('HPP Terkini')
                            ->state(fn(Produk $record) => self::getInventorySnapshot($record)['latest_batch']['hpp'] ?? null),
                        TextColumn::make('latest_batch.harga_jual')
                            ->label('Harga Jual Terkini')
                            ->state(fn(Produk $record) => self::getInventorySnapshot($record)['latest_batch']['harga_jual'] ?? null),
                        TextColumn::make('stok_opname')
                            ->label('Stok Opname')
                            ->state(fn() => null),
                        TextColumn::make('selisih')
                            ->label('Selisih')
                            ->state(fn() => null),
                    ])
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Stok Opname',
                        'printed_by' => Auth::user()?->name ?? '-',
                        'printed_at' => now()->format('d M Y H:i'),
                        'sort_key' => 'kategori.nama_kategori',
                        'group_by' => 'kategori.nama_kategori',
                        'group_label' => 'Kategori',
                    ])
                    ->summaryResolver(fn(Builder $query, Collection $records) => self::buildExportSummary($records)),
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
        return self::applyInventoryScopes(parent::getEloquentQuery()->withTrashed());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
            ->schema([
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Batch Pembelian Aktif')
                            ->description('Daftar batch stok yang masih tersedia.')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                TextEntry::make('batch_cards')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.components.inventory-batches')
                                    ->state(fn(Produk $record) => self::getInventorySnapshot($record)['batches'] ?? []),
                            ])
                            ->compact(),
                    ]),

                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Inventory Summary')
                            ->icon('heroicon-m-cube')
                            ->schema([
                                TextEntry::make('nama_produk')
                                    ->label('Nama Produk')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->formatStateUsing(fn($state) => Str::upper($state))
                                    ->icon('heroicon-m-tag'),

                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('brand.nama_brand')
                                            ->label('Brand')
                                            ->icon('heroicon-m-star')
                                            ->placeholder('-'),

                                        TextEntry::make('kategori.nama_kategori')
                                            ->label('Kategori')
                                            ->badge()
                                            ->color('gray')
                                            ->placeholder('-'),
                                    ]),

                                TextEntry::make('qty_display')
                                    ->label('Total Stok Tersedia')
                                    ->state(fn(Produk $record) => self::formatNumber(self::getInventorySnapshot($record)['qty'] ?? 0))
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('primary')
                                    ->icon('heroicon-m-circle-stack'),

                                TextEntry::make('batch_count_display')
                                    ->label('Jumlah Batch Aktif')
                                    ->state(fn(Produk $record) => self::getInventorySnapshot($record)['batch_count'] ?? 0)
                                    ->badge()
                                    ->color('success')
                                    ->formatStateUsing(fn($state) => $state . ' Batch'),
                            ]),

                        Section::make('Metadata')
                            ->compact()
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('Kode SKU')
                                    ->fontFamily(FontFamily::Mono)
                                    ->copyable(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Apply inventory scopes with StockBatch as primary source.
     * Pre-computes snapshot data and attaches it to each record.
     */
    protected static function applyInventoryScopes(Builder $query): Builder
    {
        $produkTable = (new Produk)->getTable();
        $kategoriTable = (new Kategori)->getTable();
        $stockBatchTable = (new StockBatch)->getTable();
        $qtySisaColumn = PembelianItem::qtySisaColumn();
        $activeRmaStatuses = Rma::activeStatuses();
        $placeholders = implode(',', array_fill(0, count($activeRmaStatuses), '?'));

        // Subquery for StockBatch totals (primary source)
        $stockBatchSubquery = DB::table($stockBatchTable)
            ->select('produk_id')
            ->selectRaw('SUM(qty_available) as sb_total_qty')
            ->selectRaw('COUNT(id) as sb_batch_count')
            ->where('qty_available', '>', 0)
            ->groupBy('produk_id');

        $query
            ->select("{$produkTable}.*")
            // Join StockBatch subquery
            ->leftJoinSub(
                $stockBatchSubquery,
                'sb_totals',
                "{$produkTable}.id",
                '=',
                'sb_totals.produk_id'
            )
            // Fallback: Check PembelianItem for legacy data
            ->where(function ($q) use ($produkTable, $qtySisaColumn, $activeRmaStatuses) {
                $q->whereNotNull('sb_totals.produk_id')
                    ->orWhereHas(
                        'pembelianItems',
                        fn($pi) => $pi
                            ->where($qtySisaColumn, '>', 0)
                            ->whereDoesntHave('rmas', fn($rma) => $rma->whereIn('status_garansi', $activeRmaStatuses))
                    );
            })
            ->with([
                'brand',
                'kategori',
                'stockBatches' => fn($q) => $q
                    ->where('qty_available', '>', 0)
                    ->with(['pembelianItem.pembelian', 'pembelianItem.rmas'])
                    ->orderBy('created_at'),
                'pembelianItems' => fn($q) => $q
                    ->where($qtySisaColumn, '>', 0)
                    ->whereDoesntHave('rmas', fn($rma) => $rma->whereIn('status_garansi', $activeRmaStatuses))
                    ->with(['pembelian', 'rmas']),
            ])
            // Use StockBatch totals if available, fallback to PembelianItem
            // Parameter binding untuk mencegah SQL injection
            ->selectRaw(
                "COALESCE(sb_totals.sb_total_qty, (
                    SELECT SUM({$qtySisaColumn})
                    FROM tb_pembelian_item
                    WHERE tb_pembelian_item.id_produk = {$produkTable}.id
                    AND {$qtySisaColumn} > 0
                    AND NOT EXISTS (
                        SELECT 1 FROM tb_rma
                        WHERE tb_rma.id_pembelian_item = tb_pembelian_item.id_pembelian_item
                        AND tb_rma.status_garansi IN ({$placeholders})
                    )
                ), 0) as total_qty",
                $activeRmaStatuses
            )
            ->selectRaw(
                "COALESCE(sb_totals.sb_batch_count, (
                    SELECT COUNT(*)
                    FROM tb_pembelian_item
                    WHERE tb_pembelian_item.id_produk = {$produkTable}.id
                    AND {$qtySisaColumn} > 0
                    AND NOT EXISTS (
                        SELECT 1 FROM tb_rma
                        WHERE tb_rma.id_pembelian_item = tb_pembelian_item.id_pembelian_item
                        AND tb_rma.status_garansi IN ({$placeholders})
                    )
                ), 0) as batch_count",
                $activeRmaStatuses
            )
            ->groupBy("{$produkTable}.id");

        $query->orderBy(
            Kategori::select('nama_kategori')
                ->whereColumn("{$kategoriTable}.id", "{$produkTable}.kategori_id")
        )->orderBy('nama_produk');

        return $query;
    }

    protected static array $inventorySnapshotCache = [];

    /**
     * Get inventory snapshot for a product.
     * Uses StockBatch as primary source, PembelianItem as fallback.
     * Result is cached per-request.
     *
     * @return array{
     *     qty: int,
     *     batch_count: int,
     *     batches: array<int, array<string, mixed>>,
     *     latest_batch: array<string, mixed>|null
     * }
     */
    public static function getInventorySnapshot(Produk $record): array
    {
        $cacheKey = $record->getKey();

        if (isset(self::$inventorySnapshotCache[$cacheKey])) {
            return self::$inventorySnapshotCache[$cacheKey];
        }

        $qtySisaColumn = PembelianItem::qtySisaColumn();
        $activeRmaStatuses = Rma::activeStatuses();

        // Try StockBatch first (primary source)
        if ($record->relationLoaded('stockBatches')) {
            $stockBatches = $record->stockBatches;
        } else {
            $stockBatches = $record->stockBatches()
                ->where('qty_available', '>', 0)
                ->with(['pembelianItem.pembelian', 'pembelianItem.rmas'])
                ->orderBy('created_at')
                ->get();
        }

        $activeBatches = collect();

        if ($stockBatches->isNotEmpty()) {
            // Use StockBatch data - also filter out RMA active batches
            $activeBatches = $stockBatches
                ->filter(fn($batch) => $batch->qty_available > 0)
                ->filter(function ($batch) {
                    $item = $batch->pembelianItem;
                    if (! $item) {
                        return true; // No pembelianItem means no RMA
                    }
                    return $item->rmas->whereIn('status_garansi', Rma::activeStatuses())->isEmpty();
                })
                ->sortBy(fn($batch) => $batch->created_at);
        }

        // Fallback to PembelianItem if no StockBatch data
        if ($activeBatches->isEmpty()) {
            if ($record->relationLoaded('pembelianItems')) {
                $items = $record->pembelianItems;
            } else {
                $items = $record->pembelianItems()
                    ->with(['pembelian', 'rmas'])
                    ->get();
            }

            $activeBatches = $items
                ->filter(fn($item) => (int) ($item->{$qtySisaColumn} ?? 0) > 0)
                ->filter(fn($item) => $item->rmas->whereIn('status_garansi', $activeRmaStatuses)->isEmpty())
                ->sortBy(fn($item) => $item->pembelian?->tanggal ?? $item->created_at)
                ->values();
        }

        $totalQty = $activeBatches->sum(function ($batch) use ($stockBatches) {
            if ($batch instanceof StockBatch) {
                return $batch->qty_available;
            }
            // PembelianItem fallback
            $qtySisaColumn = PembelianItem::qtySisaColumn();
            return (int) ($batch->{$qtySisaColumn} ?? 0);
        });

        $formattedBatches = $activeBatches->map(function ($batch) {
            if ($batch instanceof StockBatch) {
                $item = $batch->pembelianItem;
                $purchase = $item?->pembelian;
                $qty = $batch->qty_available;
            } else {
                $item = $batch;
                $purchase = $batch->pembelian;
                $qtySisaColumn = PembelianItem::qtySisaColumn();
                $qty = (int) ($batch->{$qtySisaColumn} ?? 0);
            }

            $tanggal = $purchase && $purchase->tanggal
                ? $purchase->tanggal->format('d M Y')
                : '-';

            $hpp = is_null($item?->hpp) ? null : (int) $item->hpp;
            $hargaJual = is_null($item?->harga_jual) ? null : (int) $item->harga_jual;

            return [
                'pembelian_id' => $purchase?->id_pembelian ?? null,
                'no_po' => $purchase?->no_po ?? '-',
                'tanggal' => $tanggal,
                'qty' => $qty,
                'hpp' => $hpp,
                'harga_jual' => $hargaJual,
                'hpp_display' => is_null($hpp) ? null : self::formatCurrency($hpp),
                'harga_jual_display' => is_null($hargaJual) ? null : self::formatCurrency($hargaJual),
                'kondisi' => ucfirst($item?->kondisi ?? '-'),
            ];
        })->values()->toArray();

        $latestBatchRecord = $activeBatches->last();
        $latestBatch = null;

        if ($latestBatchRecord) {
            if ($latestBatchRecord instanceof StockBatch) {
                $item = $latestBatchRecord->pembelianItem;
            } else {
                $item = $latestBatchRecord;
            }

            $latestHpp = $item?->hpp;
            $latestHargaJual = $item?->harga_jual;
            $purchase = $item?->pembelian;

            $latestBatch = [
                'pembelian_id' => $purchase?->id_pembelian,
                'no_po' => $purchase?->no_po,
                'hpp' => is_null($latestHpp) ? null : (int) $latestHpp,
                'harga_jual' => is_null($latestHargaJual) ? null : (int) $latestHargaJual,
                'tanggal' => optional($purchase?->tanggal)->format('d M Y'),
            ];
        }

        $snapshot = [
            'qty' => $totalQty,
            'batch_count' => $activeBatches->count(),
            'batches' => $formattedBatches,
            'latest_batch' => $latestBatch,
        ];

        return self::$inventorySnapshotCache[$cacheKey] = $snapshot;
    }

    protected static function formatNumber($value): string
    {
        return number_format((int) ($value ?? 0), 0, ',', '.');
    }

    protected static function formatCurrency($value): string
    {
        $amount = (int) ($value ?? 0);
        return Money::IDR($amount, true)->formatWithoutZeroes();
    }

    public static function buildExportSummary(Collection $records): array
    {
        $totalProduk = $records->count();
        $totalStok = $records->sum(fn(Produk $record) => (int) ($record->total_qty ?? 0));
        $totalHpp = 0;
        $totalHargaJual = 0;

        foreach ($records as $record) {
            $snapshot = $record->inventory_snapshot ?? self::getInventorySnapshot($record);
            $qty = (int) ($record->total_qty ?? 0);
            $hpp = (int) ($snapshot['latest_batch']['hpp'] ?? 0);
            $hargaJual = (int) ($snapshot['latest_batch']['harga_jual'] ?? 0);

            $totalHpp += $qty * $hpp;
            $totalHargaJual += $qty * $hargaJual;
        }

        return [
            ['label' => 'Total Produk', 'value' => self::formatNumber($totalProduk)],
            ['label' => 'Total Stok Sistem', 'value' => self::formatNumber($totalStok)],
            ['label' => 'Estimasi Nilai HPP', 'value' => self::formatCurrency($totalHpp)],
            ['label' => 'Estimasi Nilai Jual', 'value' => self::formatCurrency($totalHargaJual)],
        ];
    }
}
