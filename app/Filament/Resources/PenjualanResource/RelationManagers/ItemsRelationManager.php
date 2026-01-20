<?php

namespace App\Filament\Resources\PenjualanResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produk Terjual';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('id_produk')
                ->label('Produk')
                ->options(function (Get $get): array {
                    $options = PenjualanResource::getAvailableProductOptions();
                    $selectedId = (int) ($get('id_produk') ?? 0);

                    if ($selectedId > 0 && !array_key_exists($selectedId, $options)) {
                        $label = \App\Models\Produk::query()
                            ->where('id', $selectedId)
                            ->value('nama_produk');

                        if ($label) {
                            $options[$selectedId] = $label . ' (stok habis)';
                        }
                    }

                    return $options;
                })
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->native(false)
                ->disabledOn(['edit'])
                ->afterStateUpdated(function (Set $set, ?int $state, Get $get): void {
                    $set('harga_jual', null);
                    $options = $this->getConditionOptions((int) ($state ?? 0));
                    $selected = null;

                    if (count($options) === 1) {
                        $selected = array_key_first($options);
                        $set('kondisi', $selected);
                    } elseif (! array_key_exists($get('kondisi'), $options)) {
                        $set('kondisi', null);
                    } else {
                        $selected = $get('kondisi');
                    }

                    $set('harga_jual', $this->getDefaultPriceForProduct((int) ($state ?? 0), $selected));
                }),
            Select::make('kondisi')
                ->label('Kondisi')
                ->options(fn(Get $get): array => $this->getConditionOptions((int) ($get('id_produk') ?? 0)))
                ->native(false)
                ->reactive()
                ->disabled(function (Get $get, string $operation): bool {
                    $options = $this->getConditionOptions((int) ($get('id_produk') ?? 0));

                    return $operation === 'edit' || count($options) <= 1;
                })
                ->afterStateHydrated(function (Set $set, ?string $state, Get $get): void {
                    if ($state) {
                        return;
                    }

                    $options = $this->getConditionOptions((int) ($get('id_produk') ?? 0));

                    if (count($options) === 1) {
                        $set('kondisi', array_key_first($options));
                    }
                })
                ->placeholder(function (Get $get): string {
                    $options = $this->getConditionOptions((int) ($get('id_produk') ?? 0));

                    if (empty($options)) {
                        return 'Kondisi mengikuti batch';
                    }

                    if (count($options) === 1) {
                        return 'Otomatis: ' . reset($options);
                    }

                    return 'Pilih kondisi (' . implode(' / ', array_values($options)) . ')';
                })
                ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                    $productId = (int) ($get('id_produk') ?? 0);
                    $set('harga_jual', $this->getDefaultPriceForProduct($productId, $state));
                })
                ->nullable(),
            TextInput::make('qty')
                ->label('Qty')
                ->numeric()
                ->minValue(1)
                ->required()
                ->helperText(function (Get $get): string {
                    $productId = (int) ($get('id_produk') ?? 0);

                    if ($productId < 1) {
                        return 'Pilih produk terlebih dahulu.';
                    }

                    $available = self::getAvailableQty($productId, $get('kondisi'));

                    return 'Stok tersedia: ' . number_format($available, 0, ',', '.');
                })
                ->reactive()
                ->afterStateUpdated(function (Set $set, Get $get, ?int $state): void {
                    $qty = (int) ($state ?? 0);
                    $set('serials', $this->normalizeSerials($get('serials'), $qty));
                }),
            TextInput::make('harga_jual')
                ->label('Harga Jual')
                ->numeric()
                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                ->stripCharacters([',', '.', 'Rp', ' '])
                ->minValue(0)
                ->prefix('Rp ')
                ->helperText('Kosongkan untuk mengikuti harga batch tertua.')
                ->nullable(),
            TableRepeater::make('serials')
                ->label('SN & Garansi')
                ->childComponents([
                    TextInput::make('sn')
                        ->label('SN')
                        ->maxLength(255),
                    TextInput::make('garansi')
                        ->label('Garansi')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->dehydrated()
                ->defaultItems(0)
                ->disableItemCreation()
                ->disableItemDeletion()
                ->disableItemMovement()
                ->reactive()
                ->afterStateHydrated(function (Set $set, Get $get): void {
                    $qty = (int) ($get('qty') ?? 0);
                    $set('serials', $this->normalizeSerials($get('serials'), $qty));
                })
                ->visible(fn(Get $get) => (int) ($get('qty') ?? 0) > 0),
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
                TextColumn::make('serials_sn')
                    ->label('SN')
                    ->state(function (PenjualanItem $record): string {
                        $serials = is_array($record->serials ?? null) ? $record->serials : [];
                        $snList = collect($serials)->pluck('sn')->filter()->values();

                        if ($snList->isNotEmpty()) {
                            return $snList->implode(', ');
                        }

                        return $record->produk?->sn ?? '-';
                    })
                    ->wrap(),
                TextColumn::make('serials_garansi')
                    ->label('Garansi')
                    ->state(function (PenjualanItem $record): string {
                        $serials = is_array($record->serials ?? null) ? $record->serials : [];
                        $garansiList = collect($serials)->pluck('garansi')->filter()->values();

                        if ($garansiList->isNotEmpty()) {
                            return $garansiList->map(fn($val) => $val)->implode(', ');
                        }

                        $garansi = $record->produk?->garansi;

                        return $garansi ?: '-';
                    }),
                TextColumn::make('pembelianItem.pembelian.no_po')
                    ->label('No. PO')
                    ->placeholder('-'),
                TextColumn::make('pembelianItem.pembelian.tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->placeholder('-'),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->numeric()
                    ->extraAttributes(['style' => 'width: 80px;']),
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.')),
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->extraAttributes(['style' => 'width: 100px;']),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Produk')
                    ->using(fn(array $data): PenjualanItem => $this->createItemWithAutoBatch($data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function createItemWithAutoBatch(array $data): PenjualanItem
    {
        /** @var Penjualan $penjualan */
        $penjualan = $this->getOwnerRecord();

        $productId = (int) ($data['id_produk'] ?? 0);
        $qty = (int) ($data['qty'] ?? 0);

        if ($productId < 1) {
            throw ValidationException::withMessages([
                'id_produk' => 'Pilih produk terlebih dahulu.',
            ]);
        }

        if ($qty < 1) {
            throw ValidationException::withMessages([
                'qty' => 'Qty minimal 1.',
            ]);
        }

        $customPrice = $data['harga_jual'] ?? null;
        $customPrice = ($customPrice === '' || $customPrice === null) ? null : (int) $customPrice;
        $condition = $data['kondisi'] ?? null;
        $serials = is_array($data['serials'] ?? null) ? $data['serials'] : [];

        if (! empty($serials) && count($serials) !== $qty) {
            throw ValidationException::withMessages([
                'serials' => 'Jumlah SN harus sama dengan Qty.',
            ]);
        }

        return DB::transaction(function () use ($penjualan, $productId, $qty, $customPrice, $condition, $serials): PenjualanItem {
            $created = $this->fulfillUsingFifo($penjualan, $productId, $qty, $customPrice, $condition, $serials);

            if ($created->isEmpty()) {
                throw ValidationException::withMessages([
                    'qty' => 'Tidak ada batch tersedia untuk produk tersebut.',
                ]);
            }

            return $created->last();
        });
    }

    protected function fulfillUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?int $customPrice, ?string $condition, array $serials = []): Collection
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $batchesQuery = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->orderBy('id_pembelian_item')
            ->lockForUpdate();

        if ($condition) {
            $batchesQuery->where('kondisi', $condition);
        }

        $batches = $batchesQuery->get();
        $available = (int) $batches->sum(fn(PembelianItem $batch): int => (int) ($batch->{$qtyColumn} ?? 0));

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'qty' => 'Qty melebihi stok tersedia (' . $available . ').',
            ]);
        }

        $remaining = $qty;
        $created = collect();
        $serials = array_values($serials);

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);

            $takeSerials = [];
            if (! empty($serials)) {
                $takeSerials = array_splice($serials, 0, $takeQty);
            }

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->getKey(),
                'qty' => $takeQty,
                'harga_jual' => $customPrice,
                'kondisi' => $condition,
                'serials' => empty($takeSerials) ? null : $takeSerials,
            ]);

            $created->push($record);
            $remaining -= $takeQty;
        }

        return $created;
    }

    private function normalizeSerials($serials, int $qty): array
    {
        $list = is_array($serials) ? array_values($serials) : [];

        if ($qty < 1) {
            return [];
        }

        $normalized = array_slice($list, 0, $qty);

        while (count($normalized) < $qty) {
            $normalized[] = ['sn' => null, 'garansi' => null];
        }

        return $normalized;
    }

    protected function getConditionOptions(int $productId): array
    {
        if ($productId < 1) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->pluck('kondisi')
            ->filter()
            ->unique()
            ->mapWithKeys(fn(string $condition): array => [$condition => ucfirst(strtolower($condition))])
            ->toArray();
    }

    protected function getDefaultPriceForProduct(int $productId, ?string $condition = null): ?int
    {
        $batch = $this->getOldestAvailableBatch($productId, $condition);

        return $batch?->harga_jual;
    }

    protected function getAvailableQty(int $productId, ?string $condition): int
    {
        if ($productId < 1) {
            return 0;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $query = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0);

        if ($condition) {
            $query->where('kondisi', $condition);
        }

        return $query->sum($qtyColumn);
    }

    protected function getOldestAvailableBatch(int $productId, ?string $condition = null): ?PembelianItem
    {
        if ($productId < 1) {
            return null;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when($condition, fn($query) => $query->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->first();
    }
}
