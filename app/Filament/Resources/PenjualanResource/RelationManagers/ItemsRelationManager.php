<?php

namespace App\Filament\Resources\PenjualanResource\RelationManagers;

use App\Filament\Resources\PenjualanResource;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                ->options(fn () => PenjualanResource::getAvailableProductOptions())
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
                ->options(fn (Get $get): array => $this->getConditionOptions((int) ($get('id_produk') ?? 0)))
                ->native(false)
                ->reactive()
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
                ->disabledOn(['edit'])
                ->nullable(),
            TextInput::make('qty')
                ->label('Qty')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('hpp')
                ->label('HPP')
                ->disabled()
                ->numeric()
                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                ->stripCharacters([',', '.', 'Rp', ' '])
                ->minValue(0)
                ->helperText('Otomatis mengikuti batch terpilih.')
                ->required(),
            TextInput::make('harga_jual')
                ->label('Harga Jual')
                ->numeric()
                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                ->stripCharacters([',', '.', 'Rp', ' '])
                ->minValue(0)
                ->prefix('Rp ')
                ->helperText('Kosongkan untuk mengikuti harga batch tertua.')
                ->nullable(),
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
                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) ($state ?? 0), 0, ',', '.')),
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Produk')
                    ->using(fn (array $data): PenjualanItem => $this->createItemWithAutoBatch($data)),
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
        $customPrice = ($customPrice === '' || $customPrice === null) ? null : (float) $customPrice;
        $condition = $data['kondisi'] ?? null;

        return DB::transaction(function () use ($penjualan, $productId, $qty, $customPrice, $condition): PenjualanItem {
            $created = $this->fulfillUsingFifo($penjualan, $productId, $qty, $customPrice, $condition);

            if ($created->isEmpty()) {
                throw ValidationException::withMessages([
                    'qty' => 'Tidak ada batch tersedia untuk produk tersebut.',
                ]);
            }

            return $created->last();
        });
    }

    protected function fulfillUsingFifo(Penjualan $penjualan, int $productId, int $qty, ?float $customPrice, ?string $condition): Collection
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
        $available = (int) $batches->sum(fn (PembelianItem $batch): int => (int) ($batch->{$qtyColumn} ?? 0));

        if ($available < $qty) {
            throw ValidationException::withMessages([
                'qty' => 'Qty melebihi stok tersedia (' . $available . ').',
            ]);
        }

        $remaining = $qty;
        $created = collect();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $batchAvailable = (int) ($batch->{$qtyColumn} ?? 0);

            if ($batchAvailable <= 0) {
                continue;
            }

            $takeQty = min($remaining, $batchAvailable);

            $record = PenjualanItem::query()->create([
                'id_penjualan' => $penjualan->getKey(),
                'id_produk' => $productId,
                'id_pembelian_item' => $batch->getKey(),
                'qty' => $takeQty,
                'harga_jual' => $customPrice,
                'kondisi' => $condition,
            ]);

            $created->push($record);
            $remaining -= $takeQty;
        }

        return $created;
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
            ->mapWithKeys(fn (string $condition): array => [$condition => ucfirst(strtolower($condition))])
            ->toArray();
    }

    protected function getDefaultPriceForProduct(int $productId, ?string $condition = null): ?float
    {
        $batch = $this->getOldestAvailableBatch($productId, $condition);

        return $batch?->harga_jual;
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
            ->when($condition, fn ($query) => $query->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->first();
    }
}
