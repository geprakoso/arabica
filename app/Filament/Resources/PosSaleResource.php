<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Jasa;
use Filament\Tables;
use App\Models\Gudang;
use App\Models\Member;
use App\Models\Produk;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Karyawan;
use Filament\Forms\Form;
use App\Models\Penjualan;
use App\Enums\MetodeBayar;
use Filament\Tables\Table;
use App\Models\PembelianItem;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\PosSaleResource\Pages;
use Filament\Forms\Components\Livewire as LivewireComponent;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

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
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            Forms\Components\Section::make('Informasi Penjualan')
                                ->description('Lengkapi data transaksi sebelum menambahkan item.')
                                ->columns(4)
                                ->schema([
                                    Forms\Components\DatePicker::make('tanggal_penjualan')
                                        ->label('Tanggal')
                                        ->required()
                                        ->native(false)
                                        ->default(now())
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('id_member')
                                        ->label('Member')
                                        ->options(Member::query()->pluck('nama_member', 'id'))
                                        ->formatStateUsing(fn($state) => $state ? (int) $state : null)
                                        ->searchable()
                                        ->required()
                                        ->columnSpan(2),
                                    Forms\Components\Select::make('id_karyawan')
                                        ->label('Kasir')
                                        ->options(Karyawan::query()->pluck('nama_karyawan', 'id'))
                                        ->searchable()
                                        ->nullable()
                                        ->columnSpan(1),
                                    Forms\Components\Select::make('gudang_id')
                                        ->label('Gudang')
                                        ->options(Gudang::query()->pluck('nama_gudang', 'id'))
                                        ->searchable()
                                        ->nullable()
                                        ->columnSpan(1),
                                    Forms\Components\Textarea::make('catatan')
                                        ->label('Catatan')
                                        ->rows(2)
                                        ->nullable()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Step::make('Keranjang')
                        ->icon('heroicon-m-shopping-cart')
                        ->schema([
                            Forms\Components\Section::make('Keranjang Produk')
                                ->description('Batch dipilih otomatis berdasarkan FIFO stok.')
                                ->icon('heroicon-m-cube')
                                ->schema([
                                    TableRepeater::make('items')
                                        ->createItemButtonLabel('Tambah Produk')
                                        ->addAction(fn(Action $action) => $action->color('primary'))
                                        ->label('Produk')
                                        ->minItems(0)
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
                                                        ->pluck('nama_produk', 'id')
                                                        ->map(fn(string $name) => strtoupper($name))
                                                        ->toArray();
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
                                                ->currencyMask(
                                                    thousandSeparator: '.',
                                                    decimalSeparator: ',',
                                                    precision: 0,
                                                )
                                                ->prefix('Rp')
                                                ->helperText('Kosongkan untuk pakai harga default batch lama.')
                                                ->nullable(),
                                            Forms\Components\Select::make('kondisi')
                                                ->native(false)
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
                                                        return 'Pilih Kondisi';
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
                            Forms\Components\Section::make('Jasa')
                                ->description('Setiap entri dianggap satu layanan, tanpa kolom qty terpisah.')
                                ->icon('heroicon-m-wrench-screwdriver')
                                ->schema([
                                    TableRepeater::make('services')
                                        ->label('Jasa')
                                        ->minItems(0)
                                        ->columnSpanFull()
                                        ->addAction(fn(Action $action) => $action->color('primary'))
                                        ->createItemButtonLabel('Tambah Jasa')
                                        ->helperText('Setiap entri dianggap satu layanan, tanpa kolom qty terpisah.')
                                        ->childComponents([
                                            Forms\Components\Select::make('jasa_id')
                                                ->label('Jasa')
                                                ->options(fn() => self::getAvailableServiceOptions())
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->native(false)
                                                ->reactive()
                                                ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                    $set('harga', $state ? self::getDefaultServicePrice($state) : null);
                                                }),
                                            Forms\Components\TextInput::make('harga')
                                                ->label('Harga Jasa')
                                                ->numeric()
                                                ->currencyMask(
                                                    thousandSeparator: '.',
                                                    decimalSeparator: ',',
                                                    precision: 0,
                                                )
                                                ->prefix('Rp')
                                                ->nullable(),
                                            Forms\Components\Textarea::make('catatan')
                                                ->label('Catatan')
                                                ->maxLength(255)
                                                ->nullable(),
                                        ])
                                        ->colStyles([
                                            'jasa_id' => 'width: 40%;',
                                            'harga' => 'width: 30%;',
                                            'catatan' => 'width: 30%;',
                                        ]),
                                ]),
                        ])
                        ->afterValidation(function (Get $get): void {
                            self::ensureStockIsAvailable($get('items'));
                            self::ensureCartIsNotEmpty($get('items'), $get('services'));
                        }),
                    Step::make('Ringkasan & Pembayaran')
                        ->columns(2)
                        ->schema([
                            // ringkasan transaksi
                            LivewireComponent::make('pos-cart-summary')
                                ->data(fn(Get $get): array => [
                                    'items' => $get('items') ?? [],
                                    'services' => $get('services') ?? [],
                                    'discount' => (int) ($get('diskon_total') ?? 0),
                                ])
                                ->key(function (Get $get): string {
                                    $payload = [
                                        'items' => $get('items') ?? [],
                                        'services' => $get('services') ?? [],
                                        'discount' => (int) ($get('diskon_total') ?? 0),
                                    ];

                                    return 'pos-cart-summary-' . md5(json_encode($payload));
                                })
                                ->reactive()
                                ->columnSpan(2),
                            Forms\Components\Section::make('Pembayaran')
                                ->columns(2)
                                ->schema([
                                    Select::make('metode_bayar')
                                        ->native(false)
                                        ->options(MetodeBayar::labels())
                                        ->label('Metode Bayar')
                                        ->required(),
                                    TextInput::make('diskon_total')
                                        ->label('Diskon Transaksi')
                                        ->numeric()
                                        ->currencyMask(
                                            thousandSeparator: '.',
                                            decimalSeparator: ',',
                                            precision: 0,
                                        )
                                        ->prefix('Rp')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get): void {
                                            [, $totalAmount] = self::summarizeCart($get('items'), $get('services'));
                                            $discount = max(0, (int) ($state ?? 0));
                                            $discount = min($discount, $totalAmount);
                                            $set('diskon_total', $discount);

                                            self::refreshChangeField($set, $get, null, $discount);
                                        }),
                                    Forms\Components\TextInput::make('tunai_diterima')
                                        ->label('Tunai Diterima')
                                        ->numeric()
                                        ->required()
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (Set $set, $state, Get $get): void {
                                            self::refreshChangeField($set, $get, $state);
                                        })
                                        ->rule(function (Get $get) {
                                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                [, $totalAmount] = self::summarizeCart($get('items'), $get('services'));
                                                $discount = (int) ($get('diskon_total') ?? 0);
                                                $grandTotal = max(0, $totalAmount - $discount);
                                                if ($value < $grandTotal) {
                                                    $fail("Tunai Kurang Dari Harga Total (Rp " . number_format($grandTotal, 0, ',', '.') . ")");
                                                }
                                            };
                                        }),
                                    Forms\Components\TextInput::make('kembalian')
                                        ->label('Kembalian')
                                        ->numeric()
                                        ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                        ->prefix('Rp')
                                        ->disabled(),
                                ]),
                            Forms\Components\Placeholder::make('stock_protection_hint')
                                ->label('Perlindungan Stok')
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
                Tables\Columns\TextColumn::make('metode_bayar')
                    ->icon('heroicon-o-credit-card')
                    ->formatStateUsing(fn(?MetodeBayar $state) => $state?->label()),
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
     * Menghitung total qty produk dan total nominal (produk + jasa)
     */
    public static function summarizeCart(?array $items, ?array $services = null): array
    {
        $productItems = collect($items ?? []);
        $serviceItems = collect($services ?? []);

        $totalQty = (int) $productItems->sum(fn(array $item) => (int) ($item['qty'] ?? 0));

        $productsTotal = (int) $productItems->sum(function (array $item) {
            $qty = (int) ($item['qty'] ?? 0);
            $price = self::resolveUnitPrice($item);
            $discount = (int) ($item['diskon'] ?? 0);

            return max(0, ($price * $qty) - $discount);
        });

        $servicesTotal = (int) $serviceItems->sum(function (array $service) {
            $price = (int) ($service['harga'] ?? 0);

            return max(0, $price);
        });

        return [$totalQty, $productsTotal + $servicesTotal];
    }

    /**
     * Memastikan stok tersedia untuk setiap item dalam keranjang 
     * 
     * @param ?array $items
     * @throws ValidationException
     */
    public static function ensureStockIsAvailable(?array $items): void
    {
        $items = $items ?? [];
        $requests = [];

        foreach ($items as $index => $item) {
            $productId = (int) ($item['id_produk'] ?? 0);
            $qty = (int) ($item['qty'] ?? 0);

            if ($productId < 1 || $qty < 1) {
                continue;
            }

            $condition = $item['kondisi'] ?? null;
            $key = $productId . '|' . ($condition ?? '*');

            if (! isset($requests[$key])) {
                $requests[$key] = [
                    'product_id' => $productId,
                    'condition' => $condition,
                    'qty' => 0,
                    'indexes' => [],
                ];
            }

            $requests[$key]['qty'] += $qty;
            $requests[$key]['indexes'][] = $index;
        }

        if (! $requests) {
            return;
        }

        $errors = [];

        foreach ($requests as $request) {
            $available = self::getAvailableStockForProduct($request['product_id'], $request['condition']);

            if ($request['qty'] <= $available) {
                continue;
            }

            $message = 'Qty melebihi stok tersedia (' . $available . ').';

            foreach ($request['indexes'] as $index) {
                $errors["items.$index.qty"] = $message;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function ensureCartIsNotEmpty(?array $items, ?array $services): void
    {
        if (blank($items) && blank($services)) {
            throw ValidationException::withMessages([
                'items' => 'Tambahkan minimal satu produk atau jasa.',
            ]);
        }
    }

    /**
     * Mengambil total stok tersedia untuk produk tertentu dan kondisi tertentu
     *
     * @param int $productId
     * @param ?string $condition
     * @return int
     */

    protected static function getAvailableStockForProduct(int $productId, ?string $condition = null): int
    {
        if ($productId < 1) {
            return 0;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return (int) PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when(
                filled($condition),
                fn($query) => $query->where('kondisi', $condition)
            )
            ->sum($qtyColumn);
    }

    /**
     * Menghitung dan memperbarui field kembalian
     *
     * @param Set $set
     * @param Get $get
     * @param mixed $overrideCash
     * @param ?int $overrideDiscount
     * @return void
     */

    protected static function refreshChangeField(Set $set, Get $get, $overrideCash = null, ?int $overrideDiscount = null): void
    {
        $cashValue = $overrideCash ?? $get('tunai_diterima');

        if ($cashValue === null || $cashValue === '') {
            $set('kembalian', null);
            return;
        }

        [, $totalAmount] = self::summarizeCart($get('items'), $get('services'));
        $discount = $overrideDiscount ?? (int) ($get('diskon_total') ?? 0);
        $discount = min(max($discount, 0), $totalAmount);

        $grandTotal = max(0, $totalAmount - $discount);
        $set('kembalian', max(0, (int) $cashValue - $grandTotal));
    }

    /**
     * Menghitung harga satuan
     *
     * @param array $item
     * @return int
     */
    protected static function resolveUnitPrice(array $item): int
    {
        $price = $item['harga_jual'] ?? null;

        if ($price !== null && $price !== '') {
            return (int) $price;
        }

        $productId = isset($item['id_produk']) ? (int) $item['id_produk'] : null;
        $condition = $item['kondisi'] ?? null;

        return (int) (self::getDefaultPriceForProduct($productId, $condition) ?? 0);
    }

    /**
     * Mengambil harga satuan produk
     *
     * @param ?int $productId
     * @param ?string $condition
     * @return ?int
     */

    protected static function getDefaultPriceForProduct(?int $productId, ?string $condition = null): ?int
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

    public static function getAvailableServiceOptions(): array
    {
        return Jasa::query()
            ->where('is_active', true)
            ->orderBy('nama_jasa')
            ->pluck('nama_jasa', 'id')
            ->all();
    }

    protected static function getDefaultServicePrice(?int $serviceId): ?int
    {
        if (! $serviceId) {
            return null;
        }

        return Jasa::query()->whereKey($serviceId)->value('harga');
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
