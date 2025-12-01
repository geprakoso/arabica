<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PosSaleResource\Pages;
use App\Models\Gudang;
use App\Models\Member;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Produk;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PosSaleResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'POS';

    protected static ?string $modelLabel = 'POS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Informasi Penjualan')
                        ->columns(2)
                        ->schema([
                            Forms\Components\DatePicker::make('tanggal_penjualan')
                                ->required()
                                ->native(false)
                                ->default(now()),
                            Forms\Components\Select::make('id_member')
                                ->label('Member')
                                ->options(Member::query()->pluck('nama_member', 'id'))
                                ->searchable()
                                ->nullable(),
                            Forms\Components\Select::make('gudang_id')
                                ->label('Gudang')
                                ->options(Gudang::query()->pluck('nama_gudang', 'id'))
                                ->searchable()
                                ->nullable(),
                            Forms\Components\Textarea::make('catatan')
                                ->columnSpanFull(),
                        ]),
                    Step::make('Keranjang')
                        ->schema([
                            TableRepeater::make('items')
                                ->label('Item')
                                ->minItems(1)
                                ->columnSpanFull()
                                ->helperText('Batch akan dipilih otomatis berdasarkan stok tertua (FIFO).')
                                ->childComponents([
                                    Forms\Components\Select::make('id_produk')
                                        ->label('Produk')
                                        ->options(function () {
                                            $qtyColumn = PembelianItem::qtySisaColumn();

                                            return Produk::query()
                                                ->whereHas('pembelianItems', fn($q) => $q->where($qtyColumn, '>', 0))
                                                ->orderBy('nama_produk')
                                                ->pluck('nama_produk', 'id');
                                        })
                                        ->searchable()
                                        ->reactive()
                                        ->afterStateUpdated(function (Set $set, ?int $state, Get $get): void {
                                            if (! $state) {
                                                $set('harga_jual', null);
                                                $set('kondisi', null);
                                                return;
                                            }

                                            $conditions = self::getConditionOptionsForProduct($state);
                                            $selectedCondition = null;

                                            if (count($conditions) === 1) {
                                                $selectedCondition = array_key_first($conditions);
                                                $set('kondisi', $selectedCondition);
                                            } elseif (array_key_exists($get('kondisi'), $conditions)) {
                                                $selectedCondition = $get('kondisi');
                                            } else {
                                                $set('kondisi', null);
                                            }

                                            $set('harga_jual', PosSaleResource::getDefaultPriceForProduct($state, $selectedCondition));
                                        })
                                        ->required(),
                                    Forms\Components\TextInput::make('qty')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required(),
                                    Forms\Components\TextInput::make('harga_jual')
                                        ->label('Harga')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->helperText('Kosongkan untuk pakai harga default batch lama.')
                                        ->nullable(),
                                    Forms\Components\Select::make('kondisi')
                                        ->label('Kondisi')
                                        // Mengambil opsi kondisi produk. Perhatikan bahwa jika produk tidak ditemukan atau tidak memiliki kondisi, daftar opsi akan kosong.
                                        ->options(function (Get $get): array {
                                            $productId = $get('id_produk');

                                            return $productId
                                                ? self::getConditionOptionsForProduct((int) $productId)
                                                : [];
                                        })
                                        // disabled jika opsi kondisi hanya satu
                                        ->disabled(function (Get $get): bool {
                                            $options = self::getConditionOptionsForProduct((int) ($get('id_produk') ?? 0));

                                            return count($options) <= 1;
                                        })
                                        ->required(fn(Get $get): bool => count(self::getConditionOptionsForProduct((int) ($get('id_produk') ?? 0))) > 1)
                                        // placeholder jika opsi kondisi hanya satu
                                        ->placeholder(function (Get $get): string {
                                            $options = self::getConditionOptionsForProduct((int) ($get('id_produk') ?? 0));

                                            if (empty($options)) {
                                                return 'Kondisi mengikuti batch';
                                            }

                                            $labels = array_values($options);

                                            if (count($labels) === 1) {
                                                return 'Otomatis: ' . $labels[0];
                                            }

                                            return 'Pilih kondisi (' . implode(' / ', $labels) . ')';
                                        })
                                        // set harga jual berdasarkan kondisi
                                        ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                                            $productId = (int) ($get('id_produk') ?? 0);

                                            if ($productId < 1) {
                                                return;
                                            }

                                            $set('harga_jual', PosSaleResource::getDefaultPriceForProduct($productId, $state));
                                        })
                                        ->reactive()
                                        ->nullable(),
                                ])
                                ->colStyles([
                                    'id_produk' => 'width: 40%;',
                                    'qty' => 'width: 10%;',
                                    'harga_jual' => 'width: 30%;',
                                    'kondisi' => 'width: 15%;',
                                ]),
                        ]),
                    Step::make('Ringkasan & Pembayaran')
                        ->columns(2)
                        ->schema([
                            // ringkasan transaksi 
                            LivewireComponent::make('pos-cart-summary')
                                ->data(fn(Get $get): array => [
                                    'items' => $get('items') ?? [],
                                    'discount' => (float) ($get('diskon_total') ?? 0),
                                ])
                                ->key(function (Get $get): string {
                                    $payload = [
                                        'items' => $get('items') ?? [],
                                        'discount' => (float) ($get('diskon_total') ?? 0),
                                    ];

                                    return 'pos-cart-summary-' . md5(json_encode($payload));
                                })
                                ->reactive()
                                ->columnSpan(2),
                            Forms\Components\Section::make('Pembayaran')
                                ->columns(2)
                                ->schema([
                                    Forms\Components\Select::make('metode_bayar')
                                        ->options([
                                            'cash' => 'Cash',
                                            'card' => 'Kartu',
                                            'transfer' => 'Transfer',
                                            'ewallet' => 'E-Wallet',
                                        ])
                                        ->label('Metode Bayar')
                                        ->required(),
                                    Forms\Components\TextInput::make('diskon_total')
                                        ->label('Diskon Transaksi')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->prefix('Rp')
                                        ->live()
                                        // Batasi diskon agar tidak melebihi total belanja
                                        ->afterStateUpdated(function (Set $set, $state, Get $get): void {

                                            [, $totalAmount] = self::summarizeCart($get('items'));
                                            $discount = max(0, (float) ($state ?? 0));
                                            $set('diskon_total', min($discount, $totalAmount));
                                        })
                                        ->columnSpan(1),
                                    Forms\Components\TextInput::make('tunai_diterima')
                                        ->label('Tunai Diterima')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->nullable(),
                                ]),
                            Forms\Components\Placeholder::make('stock_protection_hint')
                                ->label('Perlindungan Stok')
                                ->content('Checkout akan berhenti otomatis bila qty keranjang melebihi stok batch aktif.')
                                ->helperText('Jika qty yang diminta lebih besar daripada stok gabungan batch, sistem tidak akan menyimpan transaksi sampai qty disesuaikan.')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_penjualan')->date(),
                Tables\Columns\TextColumn::make('grand_total')->money('idr', true),
                Tables\Columns\TextColumn::make('metode_bayar'),
                Tables\Columns\TextColumn::make('tunai_diterima')->money('idr', true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('kembalian')->money('idr', true)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosSales::route('/'),
            'create' => Pages\CreatePosSale::route('/create'),
        ];
    }

    /**
     * Menghitung total qty dan total amount
     *
     * @param ?array $items
     * @return array
     */
    public static function summarizeCart(?array $items): array
    {
        $collection = collect($items ?? []);

        $totalQty = (int) $collection->sum(fn(array $item) => (int) ($item['qty'] ?? 0));

        $totalAmount = (float) $collection->sum(function (array $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $price = self::resolveUnitPrice($item);
            $discount = (float) ($item['diskon'] ?? 0);

            return max(0, ($price * $qty) - $discount);
        });

        return [$totalQty, $totalAmount];
    }

    /**
     * Menghitung harga satuan
     *
     * @param array $item
     * @return float
     */
    protected static function resolveUnitPrice(array $item): float
    {
        $price = $item['harga_jual'] ?? null;

        if ($price !== null && $price !== '') {
            return (float) $price;
        }

        $productId = isset($item['id_produk']) ? (int) $item['id_produk'] : null;
        $condition = $item['kondisi'] ?? null;

        return (float) (self::getDefaultPriceForProduct($productId, $condition) ?? 0);
    }

    /**
     * Mengambil harga satuan produk
     *
     * @param ?int $productId
     * @param ?string $condition
     * @return ?float
     */

    protected static function getDefaultPriceForProduct(?int $productId, ?string $condition = null): ?float
    {
        $batch = self::getOldestAvailableBatch($productId, $condition);

        return $batch?->harga_jual;
    }

    protected static function getOldestAvailableBatch(?int $productId, ?string $condition = null): ?PembelianItem
    {
        if (! $productId) {
            return null;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when($condition, fn($query, $condition) => $query->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->first();
    }

    /**
     * Mengambil opsi kondisi produk
     *
     * @param int $productId
     * @return array
     */
    protected static function getConditionOptionsForProduct(int $productId): array
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
            ->unique()
            ->map(fn(?string $condition) => $condition !== null ? trim($condition) : null)
            ->filter(fn(?string $condition) => filled($condition))
            ->unique()
            ->mapWithKeys(fn(string $condition) => [$condition => ucfirst(strtolower($condition))])
            ->toArray();
    }

    /**
     * Menentukan apakah resource ini harus terdaftar di navigasi.
     *
     * Fungsi ini memeriksa apakah panel yang sedang aktif adalah panel 'pos'.
     * Jika ya, maka resource ini akan terdaftar di navigasi.
     *
     * @return bool True jika panel saat ini adalah 'pos', false jika tidak.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos';
    }

    /**
     * Mengembalikan URL untuk halaman navigasi.
     *
     * @return string URL untuk halaman navigasi.
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl('create');
    }
}
