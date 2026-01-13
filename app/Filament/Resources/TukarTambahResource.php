<?php

namespace App\Filament\Resources;

use App\Models\Jasa;
use App\Models\Member;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Supplier;
use Filament\Forms\Form;
use App\Models\Pembelian;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\TukarTambah;
use App\Models\AkunTransaksi;
use App\Models\PembelianItem;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\TukarTambahResource\Pages;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Support\Collection;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PembelianRelationManager;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PenjualanRelationManager;
use Illuminate\Validation\ValidationException;

class TukarTambahResource extends BaseResource
{
    protected static ?string $model = TukarTambah::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Tukar Tambah';

    protected static ?string $pluralLabel = 'Tukar Tambah';

    protected static ?string $navigationParentItem = 'Input Penjualan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->description('Detail transaksi tukar tambah barang')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('no_nota')
                                    ->label('No. Nota Utama')
                                    ->default(fn() => TukarTambah::generateNoNota())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->columnSpan(1),
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->required()
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->columnSpan(1),
                                Select::make('id_karyawan')
                                    ->label('PJ Transaksi')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::user()?->karyawan?->id)
                                    ->required()
                                    ->prefixIcon('heroicon-m-user')
                                    ->columnSpan(1),
                            ]),
                        Textarea::make('catatan')
                            ->label('Catatan Umum')
                            ->rows(2)
                            ->placeholder('Keterangan tambahan...')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Tabs::make('Input Detail')
                    ->tabs([
                        // TAB PENJUALAN
                        Tab::make('Barang Keluar (Penjualan)')
                            ->icon('heroicon-m-arrow-up-tray')
                            ->schema([
                                Group::make()
                                    ->statePath('penjualan')
                                    ->schema([
                                        Section::make('Data Penjualan')
                                            ->icon('heroicon-m-user-group')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('id_member')
                                                            ->label('Pelanggan')
                                                            ->options(fn() => Member::query()
                                                                ->orderBy('nama_member')
                                                                ->pluck('nama_member', 'id')
                                                                ->all())
                                                            ->searchable()
                                                            ->preload()
                                                            ->prefixIcon('heroicon-m-user')
                                                            ->createOptionModalHeading('Tambah Member')
                                                            ->createOptionAction(fn($action) => $action->label('Tambah Member'))
                                                            ->createOptionForm([
                                                                TextInput::make('nama_member')
                                                                    ->label('Nama Lengkap')
                                                                    ->required(),

                                                                Grid::make(2)->schema([
                                                                    TextInput::make('no_hp')
                                                                        ->label('Nomor WhatsApp / HP')
                                                                        ->tel()
                                                                        ->required()
                                                                        ->unique(table: (new Member)->getTable(), column: 'no_hp'),

                                                                    TextInput::make('email')
                                                                        ->label('Alamat Email')
                                                                        ->email()
                                                                        ->nullable(),
                                                                ]),
                                                            ])
                                                            ->createOptionUsing(fn(array $data): int => (int) Member::query()->create($data)->getKey()),

                                                        Select::make('id_karyawan')
                                                            ->label('Sales')
                                                            ->relationship('karyawan', 'nama_karyawan')
                                                            ->preload()
                                                            ->default(fn() => Auth::user()?->karyawan?->id)
                                                            ->searchable()
                                                            ->prefixIcon('heroicon-m-user-circle'),
                                                        TextInput::make('no_nota')
                                                            ->label('No. Nota Jual')
                                                            ->default(fn() => Penjualan::generateNoNota())
                                                            ->disabled()
                                                            ->dehydrated()
                                                            ->prefixIcon('heroicon-m-document'),
                                                    ]),
                                            ])
                                            ->compact(),

                                        Section::make('Daftar Barang & Jasa')
                                            ->icon('heroicon-m-shopping-bag')
                                            ->schema([
                                                \Filament\Forms\Components\Repeater::make('items')
                                                    ->label('Daftar Barang Keluar')
                                                    ->addActionLabel('+ Tambah Barang')
                                                    ->schema([
                                                        Grid::make(6)
                                                            ->schema([
                                                                Select::make('id_produk')
                                                                    ->label('Produk')
                                                                    ->options(fn() => \App\Filament\Resources\PenjualanResource::getAvailableProductOptions())
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->required()
                                                                    ->reactive()
                                                                    ->afterStateUpdated(function (Set $set, ?int $state, Get $get): void {
                                                                        $options = self::getAvailableConditionOptions((int) ($state ?? 0));
                                                                        $selected = null;

                                                                        if (count($options) === 1) {
                                                                            $selected = array_key_first($options);
                                                                            $set('kondisi', $selected);
                                                                        } elseif (! array_key_exists($get('kondisi'), $options)) {
                                                                            $set('kondisi', null);
                                                                        } else {
                                                                            $selected = $get('kondisi');
                                                                        }

                                                                        $set('harga_jual', self::getDefaultPriceForProduct((int) ($state ?? 0), $selected));

                                                                        $available = self::getAvailableQty((int) ($state ?? 0), $selected);
                                                                        $current = (int) ($get('qty') ?? 0);

                                                                        if ($available > 0 && $current > $available) {
                                                                            $set('qty', $available);
                                                                        }
                                                                    })
                                                                    ->columnSpan(2),
                                                                Select::make('kondisi')
                                                                    ->label('Kondisi')
                                                                    ->options(fn(Get $get): array => self::getAvailableConditionOptions((int) ($get('id_produk') ?? 0)))
                                                                    ->native(false)
                                                                    ->reactive()
                                                                    ->disabled(function (Get $get): bool {
                                                                        $options = self::getAvailableConditionOptions((int) ($get('id_produk') ?? 0));

                                                                        return count($options) <= 1;
                                                                    })
                                                                    ->afterStateHydrated(function (Set $set, ?string $state, Get $get): void {
                                                                        if ($state) {
                                                                            return;
                                                                        }

                                                                        $options = self::getAvailableConditionOptions((int) ($get('id_produk') ?? 0));

                                                                        if (count($options) === 1) {
                                                                            $set('kondisi', array_key_first($options));
                                                                        }
                                                                    })
                                                                    ->placeholder(function (Get $get): string {
                                                                        $options = self::getAvailableConditionOptions((int) ($get('id_produk') ?? 0));



                                                                        $labels = array_values($options);

                                                                        if (count($labels) === 1) {
                                                                            return $labels[0];
                                                                        }

                                                                        return 'Pilih kondisi (' . implode(' / ', $labels) . ')';
                                                                    })
                                                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                                        $set('harga_jual', self::getDefaultPriceForProduct($productId, $state));

                                                                        $available = self::getAvailableQty((int) ($get('id_produk') ?? 0), $state);
                                                                        $current = (int) ($get('qty') ?? 0);

                                                                        if ($available > 0 && $current > $available) {
                                                                            $set('qty', $available);
                                                                        }
                                                                    })
                                                                    ->columnSpan(1)
                                                                    ->nullable(),
                                                                TextInput::make('qty')
                                                                    ->label('Jml (Qty)')
                                                                    ->numeric()
                                                                    ->default(1)
                                                                    ->minValue(1)
                                                                    ->maxValue(function (Get $get): ?int {
                                                                        $productId = (int) ($get('id_produk') ?? 0);

                                                                        if ($productId < 1) {
                                                                            return null;
                                                                        }

                                                                        $available = self::getAvailableQty($productId, $get('kondisi'));

                                                                        return $available > 0 ? $available : null;
                                                                    })
                                                                    ->required()
                                                                    ->reactive()
                                                                    ->helperText(function (Get $get): string {
                                                                        $productId = (int) ($get('id_produk') ?? 0);

                                                                        if ($productId < 1) {
                                                                            return 'Pilih produk terlebih dahulu.';
                                                                        }

                                                                        $available = self::getAvailableQty($productId, $get('kondisi'));

                                                                        return 'Stok tersedia: ' . number_format($available, 0, ',', '.');
                                                                    })
                                                                    ->columnSpan(1),
                                                                TextInput::make('harga_jual')
                                                                    ->label('Harga Satuan')
                                                                    ->prefix('Rp')
                                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                                    ->required()
                                                                    ->columnSpan(2),
                                                            ]),

                                                        \Filament\Forms\Components\Repeater::make('serials')
                                                            ->label('Data Serial Number (SN)')
                                                            ->addActionLabel('+ Input SN')
                                                            ->schema([
                                                                Grid::make(3)
                                                                    ->schema([
                                                                        TextInput::make('sn')
                                                                            ->label('Serial Number')
                                                                            ->required()
                                                                            ->columnSpan(2),
                                                                        TextInput::make('garansi')
                                                                            ->label('Garansi (Cth: 1 Thn)')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                            ])
                                                            ->defaultItems(1)
                                                            ->reorderable(false)
                                                            ->grid(2)
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->collapsible()
                                                    ->itemLabel(fn(array $state): ?string => \App\Models\Produk::find($state['id_produk'] ?? null)?->nama_produk ?? 'Produk Belum Dipilih')
                                                    ->columns(1),

                                                TableRepeater::make('jasa_items')
                                                    ->label('Layanan Jasa (Opsional)')
                                                    ->addActionLabel('+ Tambah Jasa')
                                                    ->schema([
                                                        Select::make('jasa_id')
                                                            ->label('Jasa')
                                                            ->prefixIcon('hugeicons-tools')
                                                            ->options(fn() => Jasa::query()->orderBy('nama_jasa')->pluck('nama_jasa', 'id')->all())
                                                            ->searchable()
                                                            ->required()
                                                            ->reactive()
                                                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                                $set('harga', $state ? (int) (Jasa::query()->find($state)?->harga ?? 0) : null);
                                                            })
                                                            ->columnSpan(2),
                                                        TextInput::make('qty')
                                                            ->label('Jml')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->required(),
                                                        TextInput::make('harga')
                                                            ->label('Tarif')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->required(),
                                                    ])
                                                    ->colStyles([
                                                        'jasa_id' => 'width: 50%;',
                                                        'qty' => 'width: 10%;',
                                                        'harga' => 'width: 40%;',
                                                    ])
                                                    ->columns(4)
                                                    ->defaultItems(0)
                                                    ->collapsible()
                                                    ->cloneable(),
                                            ])
                                            ->collapsible(),

                                        Section::make('Pembayaran')
                                            ->icon('heroicon-m-banknotes')
                                            ->schema([
                                                Grid::make(2)
                                                    ->schema([
                                                        TextInput::make('diskon_total')
                                                            ->label('Diskon (Rp)')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->default(0),
                                                    ]),
                                                TableRepeater::make('pembayaran')
                                                    ->label('Metode Pembayaran')
                                                    ->addActionLabel('+ Bayar')
                                                    ->schema([
                                                        Select::make('metode_bayar')
                                                            ->label('Metode')
                                                            ->options(['cash' => 'Tunai', 'transfer' => 'Transfer'])
                                                            ->required()
                                                            ->reactive(),
                                                        Select::make('akun_transaksi_id')
                                                            ->label('Ke Akun')
                                                            ->options(fn() => AkunTransaksi::query()->where('is_active', true)->pluck('nama_akun', 'id')),
                                                        TextInput::make('jumlah')
                                                            ->label('Nominal')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->required(),
                                                    ])
                                                    ->columns(3)
                                                    ->minItems(0),
                                            ]),
                                    ]),
                            ]),

                        // TAB PEMBELIAN
                        Tab::make('Barang Masuk (Pembelian)')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->schema([
                                Group::make()
                                    ->statePath('pembelian')
                                    ->schema([
                                        Section::make('Data Pembelian')
                                            ->icon('heroicon-m-building-storefront')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('id_supplier')
                                                            ->label('Supplier')
                                                            ->options(fn() => Supplier::query()->orderBy('nama_supplier')->pluck('nama_supplier', 'id')->all())
                                                            ->searchable()
                                                            ->prefixIcon('heroicon-m-truck')
                                                            ->createOptionModalHeading('Tambah Supplier')
                                                            ->createOptionAction(fn($action) => $action->label('Tambah Supplier'))
                                                            ->createOptionForm([
                                                                Grid::make(2)->schema([
                                                                    TextInput::make('nama_supplier')
                                                                        ->label('Nama Supplier / PT')
                                                                        ->required()
                                                                        ->unique(table: (new Supplier)->getTable(), column: 'nama_supplier'),
                                                                    TextInput::make('no_hp')
                                                                        ->label('No. Handphone / WA')
                                                                        ->tel()
                                                                        ->required()
                                                                        ->unique(table: (new Supplier)->getTable(), column: 'no_hp'),

                                                                ]),
                                                                TextInput::make('alamat')
                                                                    ->label('Alamat')
                                                                    ->nullable(),
                                                            ])
                                                            ->createOptionUsing(fn(array $data): int => (int) Supplier::query()->create($data)->getKey()),
                                                        Select::make('id_karyawan')
                                                            ->label('Staff Gudang')
                                                            ->relationship('karyawan', 'nama_karyawan')
                                                            ->preload()
                                                            ->default(fn() => Auth::user()->karyawan->id)
                                                            ->searchable()
                                                            ->prefixIcon('heroicon-m-user'),
                                                        TextInput::make('no_po')
                                                            ->label('No. PO')
                                                            ->default(fn() => Pembelian::generatePO())
                                                            ->disabled()
                                                            ->dehydrated(),
                                                    ]),
                                                Grid::make(2)
                                                    ->schema([
                                                        Select::make('tipe_pembelian')
                                                            ->label('Pajak')
                                                            ->options(['non_ppn' => 'Non PPN', 'ppn' => 'PPN (11%)'])
                                                            ->default('non_ppn'),
                                                    ]),
                                            ])
                                            ->compact(),

                                        Section::make('Daftar Barang Masuk')
                                            ->icon('heroicon-m-archive-box-arrow-down')
                                            ->schema([
                                                TableRepeater::make('items')
                                                    ->label('Barang')
                                                    ->addActionLabel('+ Tambah Barang')
                                                    ->minItems(1)
                                                    ->schema([
                                                        \Filament\Forms\Components\Hidden::make('id_pembelian_item'),
                                                        Select::make('id_produk')
                                                            ->label('Produk')
                                                            ->options(fn() => \App\Models\Produk::query()->orderBy('nama_produk')->pluck('nama_produk', 'id')->all())
                                                            ->searchable()
                                                            ->required()
                                                            ->columnSpan(2),
                                                        Select::make('kondisi')
                                                            ->label('Kondisi')
                                                            ->options(['baru' => 'Baru', 'bekas' => 'Bekas'])
                                                            ->default('baru')
                                                            ->required(),
                                                        TextInput::make('qty')
                                                            ->label('Jml')
                                                            ->numeric()
                                                            ->default(1)
                                                            ->required(),
                                                        TextInput::make('hpp')
                                                            ->label('HPP (Beli)')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->required(),
                                                        TextInput::make('harga_jual')
                                                            ->label('Rencana Jual')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->required(),
                                                    ])
                                                    ->columns(6)
                                                    ->colStyles([
                                                        'id_produk' => 'width:37%',
                                                        'kondisi' => 'width:15%',
                                                        'qty' => 'width:8%',
                                                        'hpp' => 'width:20%',
                                                        'harga_jual' => 'width:25%',
                                                    ]),
                                            ]),
                                        Section::make('Pembayaran')
                                            ->icon('heroicon-m-banknotes')
                                            ->schema([
                                                TableRepeater::make('pembayaran')
                                                    ->label('Metode Pembayaran')
                                                    ->addActionLabel('+ Bayar')
                                                    ->schema([
                                                        Select::make('metode_bayar')
                                                            ->label('Metode')
                                                            ->options(['cash' => 'Tunai', 'transfer' => 'Transfer'])
                                                            ->required()
                                                            ->reactive(),
                                                        Select::make('akun_transaksi_id')
                                                            ->label('Ke Akun')
                                                            ->options(fn() => AkunTransaksi::query()->where('is_active', true)->pluck('nama_akun', 'id'))
                                                            ->required(fn(Get $get) => $get('metode_bayar') === 'transfer'),
                                                        TextInput::make('jumlah')
                                                            ->label('Nominal')
                                                            ->prefix('Rp')
                                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                            ->required(),
                                                    ])
                                                    ->columns(3)
                                                    ->minItems(0),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_tukar_tambah')
                    ->label('Kode')
                    ->state(fn(TukarTambah $record): string => $record->kode)
                    ->weight('bold')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-document-text')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('penjualan.no_nota')
                    ->label('Nota Penjualan')
                    ->icon('heroicon-m-receipt-percent')
                    ->copyable(),
                TextColumn::make('pembelian.no_po')
                    ->label('Nota Pembelian')
                    ->icon('heroicon-m-document-text')
                    ->copyable(),
            ])
            ->actions([
                Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-m-printer')
                    ->color('primary')
                    ->url(fn(TukarTambah $record) => route('tukar-tambah.invoice', $record))
                    ->openUrlInNewTab(),
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('delete')
                        ->label('Hapus')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Tukar Tambah')
                        ->modalDescription('Tukar tambah yang masih dipakai transaksi lain akan diblokir.')
                        ->action(function (Collection $records): void {
                            $failed = [];
                            $deleted = 0;

                            foreach ($records as $record) {
                                try {
                                    $record->delete();
                                    $deleted++;
                                } catch (ValidationException $exception) {
                                    $messages = collect($exception->errors())
                                        ->flatten()
                                        ->implode(' ');
                                    $failed[] = trim($messages) ?: 'Gagal menghapus tukar tambah.';
                                }
                            }

                            if (! empty($failed)) {
                                Notification::make()
                                    ->title('Sebagian gagal dihapus')
                                    ->body(implode(' ', $failed))
                                    ->danger()
                                    ->send();
                            }

                            if ($deleted > 0) {
                                Notification::make()
                                    ->title('Tukar tambah dihapus')
                                    ->body('Berhasil menghapus ' . $deleted . ' data.')
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    protected static function getAvailableConditionOptions(int $productId): array
    {
        if ($productId < 1) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();
        $labels = [
            'baru' => 'Baru',
            'bekas' => 'Bekas',
        ];

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->whereNotNull('kondisi')
            ->distinct()
            ->orderBy('kondisi')
            ->pluck('kondisi')
            ->mapWithKeys(fn(string $value): array => [$value => $labels[$value] ?? ucfirst($value)])
            ->all();
    }

    protected static function getAvailableQty(int $productId, ?string $condition): int
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

        return (int) $query->sum($qtyColumn);
    }

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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make()
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('no_nota')
                                    ->label('No. Nota')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-document-text'),
                                TextEntry::make('id_tukar_tambah')
                                    ->label('Kode')
                                    ->state(fn(TukarTambah $record): string => $record->kode)
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-arrows-right-left'),
                                TextEntry::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                                TextEntry::make('penjualan.no_nota')
                                    ->label('Nota Penjualan')
                                    ->icon('heroicon-m-receipt-percent')
                                    ->url(fn(TukarTambah $record) => $record->penjualan
                                        ? PenjualanResource::getUrl('edit', ['record' => $record->penjualan])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                                TextEntry::make('pembelian.no_po')
                                    ->label('Nota Pembelian')
                                    ->icon('heroicon-m-document-text')
                                    ->url(fn(TukarTambah $record) => $record->pembelian
                                        ? PembelianResource::getUrl('edit', ['record' => $record->pembelian])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                            ])->grow(false),
                        ])->from('md'),
                    ]),
                InfoSection::make()
                    ->schema([
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->markdown()
                            ->prose()
                            ->placeholder('Tidak ada catatan'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTukarTambahs::route('/'),
            'create' => Pages\CreateTukarTambah::route('/create'),
            'view' => Pages\ViewTukarTambah::route('/{record}'),
            'edit' => Pages\EditTukarTambah::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PenjualanRelationManager::class,
            PembelianRelationManager::class,
        ];
    }
}
