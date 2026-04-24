<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RmaResource\Pages;
use App\Models\PembelianItem;
use App\Models\Produk;
use App\Models\Rma;
use App\Support\WebpUpload;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RmaResource extends BaseResource
{
    protected static ?string $model = Rma::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'RMA';

    protected static ?string $navigationGroup = 'Inventori';

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralLabel = 'RMA';

    public static function getGloballySearchableAttributes(): array
    {
        return ['pembelianItem.pembelian.no_po', 'pembelianItem.produk.nama_produk', 'status_garansi'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(3)
                ->schema([
                    // ── Left Column (2/3 width) ──
                    Group::make()
                        ->schema([
                            Section::make('Detail Barang')
                                ->description('Pilih barang dan batch untuk klaim RMA.')
                                ->icon('heroicon-o-cube')
                                ->schema([
                                    DatePicker::make('tanggal')
                                        ->label('Tanggal')
                                        ->prefixIcon('heroicon-o-calendar')
                                        ->required()
                                        ->default(now())
                                        ->native(false)
                                        ->columnSpan(1),
                                    Select::make('produk_id')
                                        ->label('Barang')
                                        ->options(fn(?Rma $record) => self::getAvailableProductOptions($record))
                                        ->searchable()
                                        ->preload()
                                        ->allowHtml()
                                        ->native(false)
                                        ->dehydrated(false)
                                        ->reactive()
                                        ->afterStateUpdated(fn(Set $set) => $set('id_pembelian_item', null))
                                        ->default(fn(?Rma $record) => $record?->pembelianItem?->produk?->getKey())
                                        ->placeholder('Cari nama barang...')
                                        ->helperText('Hanya menampilkan barang dengan batch yang tersedia.')
                                        ->columnSpan(1),
                                    Select::make('id_pembelian_item')
                                        ->label('Batch (No. PO)')
                                        ->options(fn(Get $get, ?Rma $record) => self::getBatchOptions((int) $get('produk_id'), $record?->id_pembelian_item))
                                        ->searchable()
                                        ->required()
                                        ->native(false)
                                        ->disabled(fn(Get $get) => ! $get('produk_id'))
                                        ->placeholder('Pilih batch...')
                                        ->helperText('Pilih barang terlebih dahulu.')
                                        ->columnSpan(1),
                                ])
                                ->columns(3),

                            Section::make('Catatan Tambahan')
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->schema([
                                    Textarea::make('catatan')
                                        ->label('Catatan')
                                        ->rows(3)
                                        ->placeholder('Deskripsikan masalah barang, keluhan pelanggan, atau instruksi khusus...')
                                        ->columnSpanFull(),
                                ])
                                ->collapsible(),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // ── Right Column (1/3 width) ──
                    Group::make()
                        ->schema([
                            Section::make('Status & Info')
                                ->icon('heroicon-o-information-circle')
                                ->description('Status klaim garansi')
                                ->schema([
                                    Select::make('status_garansi')
                                        ->label('Status Garansi')
                                        ->options(self::getStatusOptions())
                                        ->required()
                                        ->native(false)
                                        ->default(Rma::STATUS_DI_PACKING),
                                    TextInput::make('rma_di_mana')
                                        ->label('Lokasi RMA')
                                        ->prefixIcon('heroicon-o-map-pin')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Contoh: Gudang pusat, Supplier A...'),

                                    Placeholder::make('created_at')
                                        ->label('Dibuat Pada')
                                        ->visible(fn($record) => $record !== null)
                                        ->content(fn($record) => $record?->created_at?->translatedFormat('d F Y, H:i')),

                                    Placeholder::make('updated_at')
                                        ->label('Terakhir Diubah')
                                        ->visible(fn($record) => $record !== null)
                                        ->content(fn($record) => $record?->updated_at?->translatedFormat('d F Y, H:i')),
                                ])
                                ->columnSpan(['lg' => 1]),
                            Section::make('Dokumentasi')
                                ->icon('heroicon-o-camera')
                                ->schema([
                                    FileUpload::make('foto_dokumen')
                                        ->label('Foto Dokumentasi')
                                        ->image()
                                        ->multiple()
                                        ->reorderable()
                                        ->panelLayout('grid')
                                        ->panelAspectRatio('1:1')
                                        ->imagePreviewHeight('100')
                                        ->disk('public')
                                        ->visibility('public')
                                        ->directory('rma/dokumentasi')
                                        ->imageResizeMode('contain')
                                        ->imageResizeTargetWidth('1920')
                                        ->imageResizeTargetHeight('1080')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                        ->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                            return WebpUpload::store($component, $file, 80);
                                        })
                                        ->openable()
                                        ->downloadable(),
                                ])
                                ->columnSpan(['lg' => 1]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-o-calendar')
                    ->sortable(),
                TextColumn::make('pembelianItem.produk.nama_produk')
                    ->label('Barang')
                    ->formatStateUsing(fn($state) => Str::upper($state))
                    ->icon('heroicon-o-cube')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),
                TextColumn::make('pembelianItem.pembelian.no_po')
                    ->label('No. PO')
                    ->badge()
                    ->color('primary')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('status_garansi')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => self::getStatusOptions()[$state] ?? $state)
                    ->color(fn(string $state) => match ($state) {
                        Rma::STATUS_DI_PACKING => 'warning',
                        Rma::STATUS_PROSES_KLAIM => 'info',
                        Rma::STATUS_SELESAI => 'success',
                        default => 'gray',
                    })
                    ->icon(fn(string $state) => match ($state) {
                        Rma::STATUS_DI_PACKING => 'heroicon-m-archive-box-arrow-down',
                        Rma::STATUS_PROSES_KLAIM => 'heroicon-m-arrow-path',
                        Rma::STATUS_SELESAI => 'heroicon-m-check-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                TextColumn::make('rma_di_mana')
                    ->label('Lokasi RMA')
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->icon('heroicon-o-chat-bubble-left')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->catatan)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status_garansi')
                    ->label('Status')
                    ->options(self::getStatusOptions()),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('gray'),
                    Tables\Actions\EditAction::make()
                        ->color('primary'),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRmas::route('/'),
            'create' => Pages\CreateRma::route('/create'),
            'view' => Pages\ViewRma::route('/{record}'),
            'edit' => Pages\EditRma::route('/{record}/edit'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoGrid::make(3)
                    ->schema([
                        // ── Left Column (2/3 width) ──
                        InfoGroup::make()
                            ->schema([
                                InfoSection::make('Informasi Barang')
                                    ->icon('heroicon-o-cube')
                                    ->schema([
                                        TextEntry::make('pembelianItem.produk.nama_produk')
                                            ->label('Nama Barang')
                                            ->weight(FontWeight::Bold)
                                            ->size(TextEntrySize::Large)
                                            ->placeholder('-')
                                            ->columnSpanFull(),

                                        InfoGrid::make(2)
                                            ->schema([
                                                TextEntry::make('pembelianItem.pembelian.no_po')
                                                    ->label('No. PO')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->copyable()
                                                    ->placeholder('-'),

                                                TextEntry::make('tanggal')
                                                    ->label('Tanggal RMA')
                                                    ->date('d M Y')
                                                    ->icon('heroicon-m-calendar')
                                                    ->placeholder('-'),
                                            ]),
                                    ]),

                                InfoSection::make('Catatan & Dokumentasi')
                                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                    ->schema([
                                        TextEntry::make('catatan')
                                            ->hiddenLabel()
                                            ->placeholder('Tidak ada catatan.')
                                            ->markdown()
                                            ->color('gray')
                                            ->columnSpanFull(),

                                        ViewEntry::make('rma_photos_gallery')
                                            ->label('Foto Dokumentasi')
                                            ->view('filament.infolists.components.rma-photos-gallery')
                                            ->state(fn(Rma $record) => [
                                                'foto_dokumen' => $record->foto_dokumen ?? [],
                                            ])
                                            ->visible(fn(Rma $record) => ! empty($record->foto_dokumen))
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // ── Right Column (1/3 width) ──
                        InfoGroup::make()
                            ->schema([
                                InfoSection::make('Status RMA')
                                    ->icon('heroicon-o-signal')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('status_garansi')
                                            ->label('Status Garansi')
                                            ->badge()
                                            ->formatStateUsing(fn(string $state) => self::getStatusOptions()[$state] ?? $state)
                                            ->color(fn(string $state) => match ($state) {
                                                Rma::STATUS_DI_PACKING => 'warning',
                                                Rma::STATUS_PROSES_KLAIM => 'info',
                                                Rma::STATUS_SELESAI => 'success',
                                                default => 'gray',
                                            })
                                            ->icon(fn(string $state) => match ($state) {
                                                Rma::STATUS_DI_PACKING => 'heroicon-m-archive-box-arrow-down',
                                                Rma::STATUS_PROSES_KLAIM => 'heroicon-m-arrow-path',
                                                Rma::STATUS_SELESAI => 'heroicon-m-check-circle',
                                                default => 'heroicon-m-question-mark-circle',
                                            }),

                                        TextEntry::make('rma_di_mana')
                                            ->label('Lokasi RMA')
                                            ->icon('heroicon-m-map-pin')
                                            ->placeholder('-'),
                                    ]),

                                InfoSection::make('Riwayat')
                                    ->icon('heroicon-o-clock')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Dibuat')
                                            ->dateTime('d M Y, H:i')
                                            ->color('gray'),

                                        TextEntry::make('updated_at')
                                            ->label('Terakhir Diubah')
                                            ->dateTime('d M Y, H:i')
                                            ->color('gray'),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }


    public static function getStatusOptions(): array
    {
        return [
            Rma::STATUS_DI_PACKING => 'Di Packing',
            Rma::STATUS_PROSES_KLAIM => 'Proses Klaim',
            Rma::STATUS_SELESAI => 'Selesai',
        ];
    }

    public static function getAvailableProductOptions(?Rma $record = null): array
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $activeStatuses = Rma::activeStatuses();

        $products = Produk::query()
            ->whereNull('deleted_at') // Exclude soft deleted products
            ->whereHas('pembelianItems', function ($query) use ($qtyColumn, $activeStatuses) {
                $query->where($qtyColumn, '>', 0)
                    ->whereDoesntHave('rmas', fn($rmaQuery) => $rmaQuery->whereIn('status_garansi', $activeStatuses));
            })
            ->with(['pembelianItems' => function ($query) use ($qtyColumn, $activeStatuses) {
                $query->where($qtyColumn, '>', 0)
                    ->whereDoesntHave('rmas', fn($rmaQuery) => $rmaQuery->whereIn('status_garansi', $activeStatuses))
                    ->with('pembelian')
                    ->orderBy('id_pembelian_item', 'asc');
            }])
            ->orderBy('nama_produk')
            ->get();

        $options = [];
        foreach ($products as $produk) {
            $namaProduk = $produk->nama_produk;
            $batches = $produk->pembelianItems
                ->values()
                ->map(fn(PembelianItem $item, int $index) => PenjualanResource::formatBatchLabel($item, $qtyColumn, $index))
                ->filter()
                ->values();

            $batchHtml = $batches->isEmpty()
                ? '<span style="color: gray;">-</span>'
                : '<span style="color: gray;">' . implode('<br>', array_map(fn(string $label) => e($label), $batches->all())) . '</span>';

            $options[$produk->id] = sprintf(
                '<span>%s</span><br>%s',
                e($namaProduk),
                $batchHtml
            );
        }

        if ($record?->pembelianItem?->produk && ! array_key_exists($record->pembelianItem->produk->getKey(), $options)) {
            $produk = $record->pembelianItem->produk;
            $options[$produk->getKey()] = sprintf(
                '<span>%s</span><br><span style="color: gray;">(batch saat ini)</span>',
                e($produk->nama_produk)
            );
        }

        return $options;
    }

    public static function getBatchOptions(?int $productId, ?int $includeBatchId = null): array
    {
        if (! $productId) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();
        $activeStatuses = Rma::activeStatuses();

        $query = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->whereDoesntHave('rmas', fn($rmaQuery) => $rmaQuery->whereIn('status_garansi', $activeStatuses));

        if ($includeBatchId) {
            $query->orWhere('id_pembelian_item', $includeBatchId);
        }

        return $query
            ->with('pembelian')
            ->orderBy('id_pembelian_item', 'asc')
            ->get()
            ->mapWithKeys(function (PembelianItem $item, int $index) use ($qtyColumn) {
                return [$item->id_pembelian_item => PenjualanResource::formatBatchLabel($item, $qtyColumn, $index)];
            })
            ->all();
    }
}
