<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\Jasa;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\RequestOrder;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker as FormsDatePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PembelianResource extends BaseResource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Input Pembelian';

    protected static ?string $pluralLabel = 'Input Pembelian';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === BAGIAN 1: HEADER & INFORMASI UTAMA ===
                FormsSection::make('Informasi Pembelian')
                    ->description('Masukan detail supplier dan tanggal transaksi.')
                    ->schema([
                        FormsGrid::make(3)->schema([
                            // Kolom 1: Identitas Dokumen
                            FormsGroup::make()->schema([
                                TextInput::make('no_po')
                                    ->label('No. PO')
                                    ->prefixIcon('heroicon-s-tag')
                                    ->required()
                                    ->default(fn() => Pembelian::generatePO()) // generate no_po otomatis
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->prefixIcon('heroicon-m-document-text'),

                                FormsDatePicker::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->native(false)
                                    ->required(),
                            ]),

                            // Kolom 2: Pihak Terkait (Supplier & Karyawan)
                            FormsGroup::make()->schema([
                                Select::make('id_supplier')
                                    ->label('Supplier')
                                    ->relationship('supplier', 'nama_supplier')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-m-building-storefront')
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
                                    ->createOptionUsing(fn(array $data): int => (int) Supplier::query()->create($data)->getKey())
                                    ->required()
                                    ->native(false),

                                Select::make('id_karyawan')
                                    ->label('PIC / Karyawan')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::user()->karyawan?->id)
                                    ->prefixIcon('heroicon-m-user')
                                    ->required()
                                    ->native(false),
                            ]),

                            // Kolom 3: Referensi & Tipe
                            FormsGroup::make()->schema([
                                Select::make('requestOrders')
                                    ->label('Referensi RO')
                                    ->relationship('requestOrders', 'no_ro')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?array $state) {
                                        // Asumsi fungsi formatRequestOrderReferences ada di model
                                        $set('catatan', self::formatRequestOrderReferences($state ?? []));
                                    })
                                    ->native(false),

                                Select::make('tipe_pembelian')
                                    ->label('Pajak')
                                    ->options([
                                        'non_ppn' => 'Non PPN',
                                        'ppn' => 'PPN (11%)',
                                    ])
                                    ->default('non_ppn')
                                    ->native(false),
                            ])
                                ->columns(2),
                            // Tab::make('Produk Dibeli')
                            // ->schema([
                            //     TableRepeater::make('items')
                            //         ->relationship('items')
                            //         ->label('Daftar Produk')
                            //         ->minItems(1)
                            //         ->schema([
                            //             Select::make('id_produk')
                            //                 ->label('Produk')
                            //                 ->relationship('produk', 'nama_produk')
                            //                 ->searchable()
                            //                 ->preload()
                            //                 ->required()
                            //                 ->native(false),
                            //             TextInput::make('hpp')
                            //                 ->label('HPP')
                            //                 ->numeric()
                            //                 ->prefix('Rp ')
                            //                 ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2) // format pemisah uang
                            //                 ->minValue(0)
                            //                 ->required(),
                            //             TextInput::make('harga_jual')
                            //                 ->label('Harga Jual')
                            //                 ->numeric()
                            //                 ->prefix('Rp ')
                            //                 ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2) // format pemisah uang
                            //                 ->minValue(0)
                            //                 ->required(),
                            //             TextInput::make('qty')
                            //                 ->label('Qty')
                            //                 ->numeric()
                            //                 ->minValue(1)
                            //                 ->required(),
                            //             Select::make('kondisi')
                            //                 ->label('Kondisi')
                            //                 ->options([
                            //                     'baru' => 'Baru',
                            //                     'bekas' => 'Bekas',
                            //                 ])
                            //                 ->default('baru')
                            //                 ->required()
                            //                 ->native(false),
                            //         ])
                            //         ->colStyles([
                            //             'hpp' => 'width: 180px;',
                            //             'harga_jual' => 'width: 180px;',
                            //             'qty' => 'width: 80px;',
                            //             'kondisi' => 'width: 150px;',
                            //         ]) // format kolom size dan alignment
                            //         ->columns(5)
                            //         ->cloneable()
                            //         ->reorderable(false),
                            // ]),
                        ]),
                    ]),

                FormsSection::make('Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->description('pembayaran split bisa transfer dan tunai')
                    ->schema([
                        TableRepeater::make('pembayaran')
                            ->label('')
                            ->relationship('pembayaran')
                            ->minItems(0)
                            ->addActionLabel('Tambah Pembayaran')
                            ->colStyles([
                                'metode_bayar' => 'width: 20%;',
                                'akun_transaksi_id' => 'width: 30%;',
                                'jumlah' => 'width: 50%;',
                            ])
                            ->childComponents([
                                Select::make('metode_bayar')
                                    ->label('Metode')
                                    ->placeholder('pilih')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                    ])
                                    ->native(false)
                                    ->required()
                                    ->reactive(),
                                Select::make('akun_transaksi_id')
                                    ->label('Akun Transaksi')
                                    ->relationship('akunTransaksi', 'nama_akun', fn(Builder $query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->placeholder('pilih')
                                    ->preload()
                                    ->native(false)
                                    ->required(fn(Get $get) => $get('metode_bayar') === 'transfer'),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)->live(onBlur: true)
                                    ->required(),
                            ])
                            ->columns(4),
                    ]),

                // === BAGIAN 2: DAFTAR BARANG (REPEATER) ===
                FormsSection::make('Item Barang')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Daftar barang yang dibeli')
                    ->schema([
                        TableRepeater::make('items')
                            ->relationship('items')
                            ->hiddenLabel() // Hilangkan label "Items" agar lebih clean
                            ->minItems(0)
                            ->columns(12) // Menggunakan grid 12 kolom agar presisi
                            ->schema([
                                Select::make('id_produk')
                                    ->label('Produk')
                                    ->relationship('produk', 'nama_produk')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems() // Mencegah duplikasi produk
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        $pricing = self::getLastRecordedPricingForProduct((int) ($state ?? 0));
                                        $set('hpp', $pricing['hpp']);
                                        $set('harga_jual', $pricing['harga_jual']);
                                    })
                                    ->columnSpan([
                                        'md' => 4, // Lebar sedang
                                        'xl' => 4,
                                    ]),

                                Select::make('kondisi')
                                    ->label('Kondisi')
                                    ->options([
                                        'baru' => 'Baru',
                                        'bekas' => 'Bekas',
                                    ])
                                    ->default('baru')
                                    ->native(false)
                                    ->required()
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan([
                                        'md' => 1,
                                        'xl' => 1,
                                    ]),

                                TextInput::make('hpp')
                                    ->label('HPP (Beli)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->placeholder(function (Get $get): ?string {
                                        $pricing = self::getLastRecordedPricingForProduct((int) $get('id_produk'));
                                        $value = $pricing['hpp'];

                                        if (is_null($value)) {
                                            return null;
                                        }

                                        return 'Rp ' . number_format((int) $value, 0, ',', '.');
                                    })
                                    ->dehydrateStateUsing(function ($state, Get $get) {
                                        if (filled($state)) {
                                            return $state;
                                        }

                                        return self::getLastRecordedPricingForProduct((int) $get('id_produk'))['hpp'];
                                    })
                                    ->required(fn(Get $get): bool => filled($get('id_produk')) && is_null(self::getLastRecordedPricingForProduct((int) $get('id_produk'))['hpp']))
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                TextInput::make('harga_jual')
                                    ->label('Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->placeholder(function (Get $get): ?string {
                                        $pricing = self::getLastRecordedPricingForProduct((int) $get('id_produk'));
                                        $value = $pricing['harga_jual'];

                                        if (is_null($value)) {
                                            return null;
                                        }

                                        return 'Rp ' . number_format((int) $value, 0, ',', '.');
                                    })
                                    ->dehydrateStateUsing(function ($state, Get $get) {
                                        if (filled($state)) {
                                            return $state;
                                        }

                                        return self::getLastRecordedPricingForProduct((int) $get('id_produk'))['harga_jual'];
                                    })
                                    ->required(fn(Get $get): bool => filled($get('id_produk')) && is_null(self::getLastRecordedPricingForProduct((int) $get('id_produk'))['harga_jual']))
                                    ->columnSpan([
                                        'md' => 3, // Sisa kolom
                                        'xl' => 3,
                                    ]),

                            ])
                            ->cloneable()
                            ->itemLabel(fn(array $state): ?string => $state['id_produk'] ?? null ? 'Produk Terpilih' : null)
                            ->colStyles([
                                'id_produk' => 'width:37%',
                                'kondisi' => 'width:15%',
                                'qty' => 'width:8%',
                                'hpp' => 'width:20%',
                                'harga_jual' => 'width:25%',
                            ])

                    ]),

                FormsSection::make('Item Jasa')
                    ->description('Daftar jasa yang di beli')
                    ->icon('hugeicons-tools')
                    ->schema([
                        TableRepeater::make('jasaItems')
                            ->relationship('jasaItems')
                            ->label('Pembelian Jasa')
                            ->addActionLabel('+ Tambah Jasa')
                            ->schema([
                                Select::make('jasa_id')
                                    ->label('Jasa')
                                    ->prefixIcon('hugeicons-tools')
                                    ->relationship('jasa', 'nama_jasa')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        $set('harga', $state ? (int) (Jasa::query()->find($state)?->harga ?? 0) : null);
                                    })
                                    ->columnSpan(2),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('harga')
                                    ->label('Tarif')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required(),
                                TextInput::make('catatan')
                                    ->label('Catatan')
                                    ->placeholder('Opsional')
                                    ->columnSpan(2),
                            ])
                            ->colStyles([
                                'jasa_id' => 'width: 35%;',
                                'qty' => 'width: 10%;',
                                'harga' => 'width: 20%;',
                                'catatan' => 'width: 35%;',
                            ])
                            ->columns(6)
                            ->defaultItems(0)
                            ->collapsible()
                            ->cloneable(),
                    ]),

                // === BAGIAN 3: PEMBAYARAN (GRID KIRI KANAN) ===


            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // === BAGIAN ATAS: HEADER DOKUMEN ===
                InfoSection::make()
                    ->schema([
                        Split::make([
                            // Kiri: Identitas PO
                            InfoGroup::make([
                                TextEntry::make('no_po')
                                    ->label('Purchase Order')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-document-text'),

                                TextEntry::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                            ]),

                            // Tengah: Supplier
                            InfoGroup::make([
                                TextEntry::make('supplier.nama_supplier')
                                    ->label('Supplier')
                                    ->weight(FontWeight::Medium)
                                    ->icon('heroicon-m-building-storefront')
                                    ->color('primary'),

                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('PIC Internal')
                                    ->icon('heroicon-m-user'),

                                TextEntry::make('tukar_tambah_link')
                                    ->label('Tukar Tambah')
                                    ->state(fn(Pembelian $record): ?string => $record->tukarTambah?->kode)
                                    ->icon('heroicon-m-arrows-right-left')
                                    ->url(fn(Pembelian $record) => $record->tukarTambah
                                        ? TukarTambahResource::getUrl('view', ['record' => $record->tukarTambah])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                            ]),

                            // Kanan: Status & Pembayaran
                            InfoGroup::make([
                                TextEntry::make('jenis_pembayaran')
                                    ->label('Pembayaran')
                                    ->badge()
                                    ->color(fn(string $state): string => $state === 'lunas' ? 'success' : 'warning')
                                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                                TextEntry::make('tipe_pembelian')
                                    ->label('Tipe Pajak')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn(string $state): string => $state === 'ppn' ? 'PPN' : 'Non-PPN'),
                            ])->grow(false), // Agar kolom kanan tidak terlalu lebar
                        ])->from('md'), // Split hanya aktif di layar medium ke atas
                    ]),

                InfoSection::make('Pembayaran')
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('total_pembayaran')
                                    ->label('Total Pembayaran')
                                    ->color('success')
                                    ->state(fn(Pembelian $record): float => $record->calculateTotalPembelian())
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('total_dibayar')
                                    ->label('Total Dibayar')
                                    ->state(fn(Pembelian $record): float => (float) ($record->pembayaran()->sum('jumlah') ?? 0))
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Bayar')
                                    ->state(function (Pembelian $record): float {
                                        $total = $record->calculateTotalPembelian();
                                        $dibayar = (float) ($record->pembayaran()->sum('jumlah') ?? 0);

                                        return max(0, $total - $dibayar);
                                    })
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ])->grow(false),
                        ])->from('md'),
                    ]),

                // === BAGIAN TENGAH: TABEL BARANG (CLEAN TABLE) ===
                InfoSection::make('Daftar Barang')
                    // ->compact() // Mengurangi padding agar lebih rapat
                    ->schema([
                        ViewEntry::make('items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pembelian-items-table')
                            ->state(fn(Pembelian $record) => $record->items()->with('produk')->get()),
                    ]),

                InfoSection::make('Daftar Jasa')
                    ->schema([
                        ViewEntry::make('jasa_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pembelian-jasa-table')
                            ->state(fn(Pembelian $record) => $record->jasaItems()->with('jasa')->get()),
                    ]),

                // === BAGIAN BAWAH: FOOTER & CATATAN ===
                InfoSection::make()
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                // Kiri: Catatan
                                InfoGroup::make([
                                    TextEntry::make('requestOrders.no_ro')
                                        ->label('Referensi RO')
                                        ->badge()
                                        ->icon('heroicon-m-paper-clip')
                                        ->color('gray')
                                        ->placeholder('-'),
                                ]),

                                // Kanan: Info Tempo (Jika ada)
                                InfoGroup::make([
                                    TextEntry::make('tempo_label')
                                        ->state('Jatuh Tempo Pembayaran')
                                        ->label('')
                                        ->alignRight()
                                        ->color('black'),

                                    TextEntry::make('tgl_tempo')
                                        ->label('')
                                        ->date('d F Y')
                                        ->icon('heroicon-m-exclamation-triangle')
                                        ->color('danger')
                                        ->size(TextEntrySize::Large)
                                        ->weight(FontWeight::Bold)
                                        ->visible(fn($record) => $record->jenis_pembayaran === 'tempo')
                                        ->alignRight(),
                                ])->visible(fn($record) => $record->jenis_pembayaran === 'tempo'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with('requestOrders'))
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('request_orders_label')
                    ->label('Request Order')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-hashtag')
                    ->state(fn(Pembelian $record) => $record->requestOrders
                        ->map(fn($ro) => '#' . $ro->no_ro)
                        ->toArray())
                    ->separator(',')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('tipe_pembelian')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'ppn',
                        'gray' => 'non_ppn',
                    ]),
                TextColumn::make('jenis_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'lunas',
                        'danger' => 'tempo',
                    ]),
                TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->icon('heroicon-m-shopping-cart')
                    ->badge()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('primary')
                        ->tooltip('Lihat Detail'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->tooltip('Edit'),
                ])
                    ->label('Aksi')
                    ->tooltip('Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'view' => Pages\ViewPembelian::route('/{record}'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }

    // mengubah array id request order jadi teks tag seperti #RO123, #RO124
    protected static function formatRequestOrderReferences(array $requestOrderIds): ?string
    {
        $ids = collect($requestOrderIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return null;
        }

        $tags = RequestOrder::query()
            ->whereIn('id', $ids)
            ->pluck('no_ro')
            ->filter()
            ->map(fn($noRo) => "#{$noRo}")
            ->toArray();

        return empty($tags) ? null : implode(', ', $tags);
    }

    protected static function getLastRecordedPricingForProduct(int $productId): array
    {
        if ($productId < 1) {
            return ['hpp' => null, 'harga_jual' => null];
        }

        $itemTable = (new PembelianItem)->getTable();
        $purchaseTable = (new Pembelian)->getTable();
        $productColumn = PembelianItem::productForeignKey();
        $primaryKey = PembelianItem::primaryKeyColumn();

        $lastItem = PembelianItem::query()
            ->leftJoin($purchaseTable, $purchaseTable . '.id_pembelian', '=', $itemTable . '.id_pembelian')
            ->where($itemTable . '.' . $productColumn, $productId)
            ->orderByDesc($purchaseTable . '.tanggal')
            ->orderByDesc($itemTable . '.' . $primaryKey)
            ->select([
                $itemTable . '.' . $primaryKey,
                $itemTable . '.hpp',
                $itemTable . '.harga_jual',
            ])
            ->first();

        if (! $lastItem) {
            return ['hpp' => null, 'harga_jual' => null];
        }

        $hpp = $lastItem->hpp;
        $hargaJual = $lastItem->harga_jual;

        return [
            'hpp' => is_null($hpp) ? null : (int) $hpp,
            'harga_jual' => is_null($hargaJual) ? null : (int) $hargaJual,
        ];
    }
}
