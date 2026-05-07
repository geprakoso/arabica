<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Filament\Resources\PenjualanResource;
use App\Models\Jasa;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\RequestOrder;

use App\Models\Supplier;
use App\Support\WebpUpload;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DatePicker as FormsDatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Actions\StaticAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PembelianResource extends BaseResource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Input Pembelian';

    protected static ?string $pluralLabel = 'Input Pembelian';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'no_po';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_po', 'supplier.nama_supplier', 'nota_supplier'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Supplier' => $record->supplier->nama_supplier,
            'Tanggal' => $record->tanggal->format('d M Y'),
        ];
    }

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
                                    ->dehydrated(),
                                FormsDatePicker::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->native(false)
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),
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
                                    ->native(false)
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),

                                Select::make('id_karyawan')
                                    ->label('PIC / Karyawan')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::user()->karyawan?->id)
                                    ->prefixIcon('heroicon-m-user')
                                    ->required()
                                    ->native(false)
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),
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
                                    ->native(false)
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),

                                Select::make('tipe_pembelian')
                                    ->label('Pajak')
                                    ->options([
                                        'non_ppn' => 'Non PPN',
                                        'ppn' => 'PPN (11%)',
                                    ])
                                    ->default('non_ppn')
                                    ->native(false)
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),

                                TextInput::make('nota_supplier')
                                    ->label('Nota Referensi')
                                    ->placeholder('Opsional')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-m-receipt-refund')
                                    ->columnSpanFull()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),
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

                // === BAGIAN 2: DAFTAR BARANG (REPEATER) ===
                // R09: Section Item Barang LOCKED saat edit - tidak bisa diubah
                FormsSection::make('Item Barang')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Daftar barang yang dibeli')
                    ->schema([
                        TableRepeater::make('items')
                            ->relationship('items')
                            ->hiddenLabel()
                            ->minItems(0)
                            // R09: LOCKED saat edit - disable semua field dan aksi
                            ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                            ->deletable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->reorderable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->addable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->cloneable(false)
                            ->columns(12)
                            ->schema([
                                // R02: Produk duplikat dengan kondisi berbeda diperbolehkan
                                Select::make('id_produk')
                                    ->label('Produk')
                                    ->relationship(
                                        name: 'produk',
                                        titleAttribute: 'nama_produk',
                                        modifyQueryUsing: fn($query) => $query->whereNull('deleted_at')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan([
                                        'md' => 4,
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
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                // R03: Qty - LOCKED saat edit
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->live(debounce: '300ms')
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $qty = (int) ($get('qty') ?? 0);
                                        $hpp = (int) ($get('hpp') ?? 0);
                                        $set('subtotal', $qty * $hpp);
                                    })
                                    ->columnSpan([
                                        'md' => 1,
                                        'xl' => 1,
                                    ]),

                                // R03: HPP - LOCKED saat edit
                                TextInput::make('hpp')
                                    ->label('HPP (Beli)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->placeholder('Masukkan HPP')
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->live(debounce: '300ms')
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $qty = (int) ($get('qty') ?? 0);
                                        $hpp = (int) ($get('hpp') ?? 0);
                                        $set('subtotal', $qty * $hpp);
                                    })
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                // R03: Harga Jual - LOCKED saat edit
                                TextInput::make('harga_jual')
                                    ->label('Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->placeholder('Masukkan harga jual')
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                // R03: Subtotal - read-only
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->readOnly()
                                    ->dehydrated(true)
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                            ])
                            ->itemLabel(fn(array $state): ?string => $state['id_produk'] ?? null ? 'Produk Terpilih' : null)
                            // R05: Items boleh kosong
                            ->minItems(0)
                            ->colStyles([
                                'id_produk' => 'width: 30%;',
                                'kondisi' => 'width: 10%;',
                                'qty' => 'width: 8%;',
                                'hpp' => 'width: 14%;',
                                'harga_jual' => 'width: 14%;',
                                'subtotal' => 'width: 16%;',
                            ]),

                    ])
                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),

                // R09: Item Jasa juga LOCKED saat edit
                FormsSection::make('Item Jasa')
                    ->description('Daftar jasa yang di beli')
                    ->icon('hugeicons-tools')
                    ->schema([
                        TableRepeater::make('jasaItems')
                            ->relationship('jasaItems')
                            ->label('Pembelian Jasa')
                            ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                            ->deletable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->reorderable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->addable(fn($livewire) => ! ($livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian))
                            ->cloneable(false)
                            ->schema([
                                Select::make('jasa_id')
                                    ->label('Jasa')
                                    ->prefixIcon('hugeicons-tools')
                                    ->relationship('jasa', 'nama_jasa')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan(2),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan(1),
                                TextInput::make('harga')
                                    ->label('Tarif')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required()
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
                                    ->columnSpan(1),
                                TextInput::make('catatan')
                                    ->label('Catatan')
                                    ->placeholder('Opsional')
                                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian)
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
                            ->collapsible(),
                    ])
                    ->disabled(fn($livewire) => $livewire instanceof \App\Filament\Resources\PembelianResource\Pages\EditPembelian),

                FormsSection::make('Total')
                    ->schema([
                        Placeholder::make('grand_total_display')
                            ->label('GRAND TOTAL')
                            ->content(function (Get $get) {
                                $items = $get('items') ?? [];
                                $jasaItems = $get('jasaItems') ?? [];

                                $totalBarang = collect($items)->sum(fn($item) => ((int) ($item['qty'] ?? 0)) * ((int) ($item['hpp'] ?? 0)));
                                $totalJasa = collect($jasaItems)->sum(fn($item) => ((int) ($item['qty'] ?? 0)) * ((int) ($item['harga'] ?? 0)));

                                return 'Rp ' . number_format($totalBarang + $totalJasa, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                    ]),

                // === BAGIAN 3: PEMBAYARAN (GRID KIRI KANAN) ===
                FormsSection::make('Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->description('pembayaran split bisa transfer dan tunai')
                    ->schema([
                        TableRepeater::make('pembayaran')
                            ->label('')
                            ->relationship('pembayaran')
                            ->minItems(0)
                            ->addActionLabel('Tambah Pembayaran')
                            ->childComponents([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->native(false)
                                    ->required(),
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
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->placeholder(function (Get $get) {
                                        $items = $get('../../items') ?? [];
                                        $jasaItems = $get('../../jasaItems') ?? [];
                                        $pembayaran = $get('../../pembayaran') ?? [];

                                        $totalBarang = collect($items)->sum(fn($item) => ((int) ($item['qty'] ?? 0)) * ((int) ($item['hpp'] ?? 0)));
                                        $totalJasa = collect($jasaItems)->sum(fn($item) => ((int) ($item['qty'] ?? 0)) * ((int) ($item['harga'] ?? 0)));
                                        $grandTotal = $totalBarang + $totalJasa;

                                        $totalPaid = collect($pembayaran)->sum(fn($p) => (int) ($p['jumlah'] ?? 0));

                                        // Total Paid includes the current value if it's in the array.
                                        // The placeholder is shown when the field is empty (value is null/empty).
                                        // So totalPaid calculated here will exclude this field's contribution effectively (0).

                                        $remaining = max(0, $grandTotal - $totalPaid);

                                        return 'Sisa: Rp. ' . number_format($remaining, 0, ',', '.');
                                    })
                                    ->required(),
                                FileUpload::make('bukti_transfer')
                                    ->label('Bukti')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('pembelian/bukti-transfer')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                        return WebpUpload::store($component, $file, 80);
                                    })
                                    ->openable()
                                    ->downloadable()
                                    ->previewable(false)
                                    // ->placeholder('Upload bukti transfer')
                                    ->extraAttributes(['class' => 'compact-file-upload'])
                                    ->helperText(new HtmlString('
                                        <style>
                                            .compact-file-upload .filepond--root,
                                            .compact-file-upload .filepond--panel-root {
                                                min-height: 38px !important;
                                                height: 38px !important;
                                                border-radius: 0.5rem;
                                            }
                                            .compact-file-upload .filepond--drop-label {
                                                min-height: 38px !important;
                                                display: flex;
                                                align-items: center;
                                                justify-content: center;
                                                transform: none !important;
                                                padding: 0 !important;
                                                color: rgb(var(--primary-600)) !important;
                                                cursor: pointer;
                                            }
                                        </style>
                                    ')),
                            ])
                            ->colStyles([
                                'metode_bayar' => 'width: 15%;',
                                'akun_transaksi_id' => 'width: 25%;',
                                'jumlah' => 'width: 35%;',
                                'bukti_transfer' => 'width: 25%;',
                            ])
                            ->columns(4),
                    ]),

                FormsSection::make('Catatan')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Textarea::make('catatan')
                            ->label('')
                            ->placeholder('Tambahkan catatan pembelian (opsional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

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

                                TextEntry::make('nota_supplier')
                                    ->label('Nota Supplier')
                                    ->icon('heroicon-m-receipt-refund')
                                    ->placeholder('-'),

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
                            ])->grow(false), // Agar kolom kanan tidak terlalu lebar

                        ])->from('md'), // Split hanya aktif di layar medium ke atas
                    ]),

                // === BAGIAN TENGAH: TABEL BARANG (CLEAN TABLE) ===
                InfoSection::make('Daftar Barang')
                    // ->compact() // Mengurangi padding agar lebih rapat
                    ->schema([
                        ViewEntry::make('items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pembelian-items-table')
                            ->state(fn(Pembelian $record) => $record->items),
                    ]),

                InfoSection::make('Daftar Jasa')
                    ->visible(fn(Pembelian $record) => $record->jasaItems->isNotEmpty())
                    ->schema([
                        ViewEntry::make('jasa_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pembelian-jasa-table')
                            ->state(fn(Pembelian $record) => $record->jasaItems),
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
                            ])->grow(),
                            InfoGroup::make([
                                TextEntry::make('total_dibayar')
                                    ->label('Total Dibayar')
                                    ->state(fn(Pembelian $record): float => (float) $record->pembayaran->sum('jumlah'))
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ])->grow(),
                            InfoGroup::make([
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Bayar')
                                    ->state(function (Pembelian $record): float {
                                        $total = $record->calculateTotalPembelian();
                                        $dibayar = (float) $record->pembayaran->sum('jumlah');

                                        return max(0, $total - $dibayar);
                                    })
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ])->grow(),
                            InfoGroup::make([
                                TextEntry::make('kelebihan_bayar')
                                    ->label('Kelebihan Bayar')
                                    ->state(function (Pembelian $record): float {
                                        $total = $record->calculateTotalPembelian();
                                        $dibayar = (float) $record->pembayaran->sum('jumlah');

                                        return max(0, $dibayar - $total);
                                    })
                                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ])->grow(false),
                        ])->from('lg'),
                    ]),

                InfoSection::make('Rincian Pembayaran')
                    ->visible(fn(Pembelian $record) => $record->pembayaran->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('pembayaran')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d/m/Y')
                                    ->placeholder('-'),
                                TextEntry::make('metode_bayar')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => $state === 'cash' ? 'Tunai' : 'Transfer')
                                    ->color(fn($state) => $state === 'cash' ? 'success' : 'info'),
                                TextEntry::make('akunTransaksi.nama_akun')
                                    ->label('Akun Transaksi')
                                    ->placeholder('-'),
                                TextEntry::make('jumlah')
                                    ->label('Jumlah')
                                    ->formatStateUsing(fn($state) => 'Rp ' . number_format((int) $state, 0, ',', '.')),
                            ])
                            ->columns(4),
                    ]),

                // === BAGIAN BAWAH: REFERENSI RO ===
                InfoSection::make()
                    ->visible(fn(Pembelian $record) => $record->requestOrders->isNotEmpty())
                    ->schema([
                        TextEntry::make('requestOrders.no_ro')
                            ->label('Referensi RO')
                            ->badge()
                            ->icon('heroicon-m-paper-clip')
                            ->color('gray'),
                    ]),

                // === CATATAN ===
                InfoSection::make('Catatan')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(Pembelian $record) => filled($record->catatan))
                    ->schema([
                        TextEntry::make('catatan')
                            ->hiddenLabel()
                            ->markdown(),
                    ]),

                InfoSection::make('Bukti & Dokumentasi')
                    ->icon('heroicon-o-camera')
                    ->visible(fn(Pembelian $record) => $record->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty() || ! empty($record->foto_dokumen))
                    ->schema([
                        ViewEntry::make('all_photos_gallery')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.pembelian-photos-gallery')
                            ->state(fn(Pembelian $record) => [
                                'bukti_pembayaran' => $record->pembayaran->whereNotNull('bukti_transfer')->pluck('bukti_transfer')->toArray(),
                                'foto_dokumen' => $record->foto_dokumen ?? [],
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with([
                'requestOrders',
                'supplier',
                'karyawan.user',
                'items',
                'jasaItems',
                'pembayaran.akunTransaksi',
            ])->withSum('pembayaran', 'jumlah'))
            ->defaultSort('created_at', 'desc')
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
                    ->date('d/m/y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->limit(9)
                    ->tooltip(fn(Pembelian $record): ?string => $record->supplier?->nama_supplier)
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nota_supplier')
                    ->label('Nota Supplier')
                    ->icon('heroicon-m-receipt-refund')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('request_orders_label')
                    ->label('Request Order')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-hashtag')
                    ->state(fn(Pembelian $record) => $record->requestOrders
                        ->map(fn($ro) => '#' . $ro->no_ro)
                        ->toArray())
                    ->separator(',')
                    ->hidden()
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable()
                    ->hidden()
                    ->sortable(),
                TextColumn::make('tipe_pembelian')
                    ->label('Tipe')
                    ->badge()
                    ->hidden()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'ppn',
                        'gray' => 'non_ppn',
                    ]),
                // R06: Hanya 2 status pembayaran - TEMPO dan LUNAS (tidak ada DP)
                TextColumn::make('status_pembayaran')
                    ->label('Tipe')
                    ->badge()
                    ->state(function (Pembelian $record): string {
                        $grandTotal = (float) $record->calculateTotalPembelian();
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);

                        // R06: LUNAS = pembayaran >= grand total
                        if ($totalPaid >= $grandTotal) {
                            return 'LUNAS';
                        }

                        // R06: TEMPO = pembayaran < grand total (termasuk 0)
                        return 'TEMPO';
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'LUNAS' => 'success',
                        'TEMPO' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('is_locked')
                    ->label('Status')
                    ->badge()
                    ->state(function (Pembelian $record): string {
                        return $record->is_locked ? 'Final' : 'Draft';
                    })
                    ->color(function (Pembelian $record): string {
                        return $record->is_locked ? 'success' : 'warning';
                    })
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->icon('heroicon-m-shopping-cart')
                    ->badge()
                    ->hidden()
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('total_pembayaran')
                    ->label('Grand Total')
                    ->icon('heroicon-m-banknotes')
                    ->state(fn(Pembelian $record) => $record->calculateTotalPembelian())
                    ->formatStateUsing(fn(float $state): string => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->color('success')
                    ->sortable(),
                TextColumn::make('sisa_bayar_display')
                    ->label('Sisa Bayar')
                    ->alignRight()
                    ->state(function (Pembelian $record): string {
                        $grandTotal = (float) $record->calculateTotalPembelian();
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);

                        $sisa = max(0, $grandTotal - $totalPaid);

                        return 'Rp ' . number_format((int) $sisa, 0, ',', '.');
                    })
                    ->copyable()
                    ->color('danger')
                    ->weight('bold'),

                ImageColumn::make('karyawan.user.avatar_url')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(
                        fn(Pembelian $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->karyawan?->nama_karyawan ?? 'User') .
                            '&color=FFFFFF&background=0D9488&size=128&bold=true'
                    )
                    ->tooltip(fn(Pembelian $record): ?string => $record->karyawan?->nama_karyawan)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('id_karyawan')
                    ->label('Karyawan')
                    ->relationship(
                        'karyawan',
                        'nama_karyawan',
                        fn(Builder $query) => $query->whereHas('pembelian')
                    )
                    ->searchable()
                    ->preload(),
                SelectFilter::make('id_supplier')
                    ->label('Supplier')
                    ->relationship(
                        'supplier',
                        'nama_supplier',
                        fn(Builder $query) => $query->whereHas('pembelian')
                    )
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('periode')
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('range')
                                ->label('Rentang Waktu')
                                ->options([
                                    'hari_ini' => 'Hari Ini',
                                    'kemarin' => 'Kemarin',
                                    '2_hari_lalu' => '2 Hari Lalu',
                                    '3_hari_lalu' => '3 Hari Lalu',
                                    'custom' => 'Custom',
                                ])
                                ->native(false)
                                ->reactive()
                                ->columnSpan(2),
                            DatePicker::make('from')
                                ->label('Mulai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('heroicon-m-calendar')
                                ->hidden(fn(Get $get) => $get('range') !== 'custom'),
                            DatePicker::make('until')
                                ->label('Sampai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('heroicon-m-calendar')
                                ->hidden(fn(Get $get) => $get('range') !== 'custom'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $range = $data['range'] ?? null;

                        if (! $range) {
                            return $query;
                        }

                        if ($range === 'hari_ini') {
                            return $query->whereDate('tanggal', now());
                        }

                        if ($range === 'custom') {
                            $startDate = $data['from'] ?? null;
                            $endDate = $data['until'] ?? null;

                            return $query
                                ->when(
                                    $startDate,
                                    fn(Builder $query, $date) => $query->whereDate('tanggal', '>=', $date),
                                )
                                ->when(
                                    $endDate,
                                    fn(Builder $query, $date) => $query->whereDate('tanggal', '<=', $date),
                                );
                        }

                        $targetDate = match ($range) {
                            'kemarin' => now()->subDay(),
                            '2_hari_lalu' => now()->subDays(2),
                            '3_hari_lalu' => now()->subDays(3),
                            default => null,
                        };

                        return $query->when(
                            $targetDate,
                            fn(Builder $query, $date) => $query->whereDate('tanggal', $date)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $range = $data['range'] ?? null;
                        if (! $range) {
                            return null;
                        }

                        if ($range === 'custom') {
                            $from = $data['from'] ?? null;
                            $until = $data['until'] ?? null;

                            if (! $from && ! $until) {
                                return null;
                            }

                            return 'Periode: ' . ($from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : '...') . ' - ' . ($until ? \Carbon\Carbon::parse($until)->format('d/m/Y') : '...');
                        }

                        return 'Periode: ' . match ($range) {
                            'hari_ini' => 'Hari Ini',
                            'kemarin' => 'Kemarin',
                            '2_hari_lalu' => '2 Hari Lalu',
                            '3_hari_lalu' => '3 Hari Lalu',
                            default => ucfirst(str_replace('_', ' ', $range)),
                        };
                    }),

            ])
            ->actions([
                // R16: Lock Final (Draft -> Final)
                Action::make('lock')
                    ->label('')
                    ->icon('heroicon-m-lock-closed')
                    ->tooltip('Lock Final')
                    ->color('danger')
                    ->visible(fn(Pembelian $record) => ! $record->is_locked)
                    ->requiresConfirmation()
                    ->modalHeading('Lock Pembelian')
                    ->modalDescription('Pembelian akan terkunci permanen. Tidak ada yang bisa diubah lagi. Lanjutkan?')
                    ->action(function (Pembelian $record) {
                        try {
                            $record->lockFinal();
                            Notification::make()
                                ->title('Pembelian berhasil di-lock')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mengunci')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->tooltip('Lihat Detail'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->tooltip('Edit')
                        ->visible(fn(Pembelian $record) => ! $record->is_locked)
                        ->action(function (Pembelian $record, \Filament\Tables\Actions\EditAction $action): void {
                            $livewire = $action->getLivewire();
                            $livewire->redirect(PembelianResource::getUrl('edit', ['record' => $record]));
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->visible(fn() => auth()->user()?->hasRole('godmode'))
                        ->modalCancelAction(false)
                        ->requiresConfirmation()
                        ->modalHeading(
                            fn(Pembelian $record): string => $record->canDelete()
                                ? 'Hapus Pembelian'
                                : 'Tidak Bisa Dihapus'
                        )
                        ->modalIcon(
                            fn(Pembelian $record): string => $record->canDelete()
                                ? 'heroicon-o-trash'
                                : 'heroicon-o-exclamation-triangle'
                        )
                        ->modalIconColor(
                            fn(Pembelian $record): string => $record->canDelete()
                                ? 'danger'
                                : 'warning'
                        )
                        ->modalDescription(function (Pembelian $record): string {
                            if ($record->canDelete()) {
                                return 'Apakah Anda yakin ingin menghapus pembelian ' . $record->no_po . '? Tindakan ini tidak dapat dibatalkan.';
                            }

                            $reasons = [];

                            if (! empty($record->no_tt)) {
                                $reasons[] = '• Terikat Tukar Tambah: ' . $record->no_tt;
                            }

                            $notas = $record->getBlockedPenjualanReferences()->pluck('nota')->filter()->values();
                            if ($notas->isNotEmpty()) {
                                $reasons[] = '• Dipakai di penjualan: ' . $notas->implode(', ');
                            }

                            return implode("\n", $reasons) ?: 'Pembelian ini tidak dapat dihapus.';
                        })
                        ->modalSubmitAction(fn(Pembelian $record) => $record->canDelete() ? null : false)
                        ->modalCancelActionLabel('Tutup')
                        ->extraModalFooterActions(function (Pembelian $record): array {
                            if ($record->canDelete()) {
                                return [];
                            }

                            return $record->getBlockedPenjualanReferences()
                                ->filter(fn(array $ref) => ! empty($ref['id']))
                                ->map(function (array $ref, int $index) {
                                    $nota = $ref['nota'] ?? null;
                                    $label = $nota ? 'Lihat ' . $nota : 'Lihat Penjualan';

                                    return StaticAction::make('viewPenjualan' . $index)
                                        ->button()
                                        ->label($label)
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->url(PenjualanResource::getUrl('view', ['record' => $ref['id']]))
                                        ->openUrlInNewTab()
                                        ->color('warning');
                                })
                                ->values()
                                ->all();
                        }),
                ])
                    ->label('Aksi')
                    ->tooltip('Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Hapus')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->visible(fn() => auth()->user()?->hasRole('godmode'))
                        ->requiresConfirmation()
                        ->modalCancelAction(false)
                        ->modalHeading('⚠️ Hapus Pembelian')
                        ->modalIcon('heroicon-o-trash')
                        ->modalIconColor('danger')
                        ->modalContent(function (\Illuminate\Database\Eloquent\Collection $records): \Illuminate\Support\HtmlString {
                            $canDelete = $records->filter(fn(Pembelian $r) => $r->canDelete());
                            $blocked   = $records->filter(fn(Pembelian $r) => ! $r->canDelete());

                            $html = '<div class="space-y-4 text-sm">';

                            if ($canDelete->isNotEmpty()) {
                                $html .= '<div>';
                                $html .= '<p class="font-semibold text-success-600 dark:text-success-400 mb-1">✅ Akan dihapus (' . $canDelete->count() . ')</p>';
                                $html .= '<ul class="space-y-1 pl-3">';
                                foreach ($canDelete as $r) {
                                    $html .= '<li class="flex items-center gap-2">'
                                        . '<span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">' . e($r->no_po) . '</span>'
                                        . '</li>';
                                }
                                $html .= '</ul>';
                                $html .= '</div>';
                            }

                            if ($blocked->isNotEmpty()) {
                                $html .= '<div>';
                                $html .= '<p class="font-semibold text-danger-600 dark:text-danger-400 mb-1">⛔ Tidak bisa dihapus (' . $blocked->count() . ')</p>';
                                $html .= '<ul class="space-y-2 pl-3">';
                                foreach ($blocked as $r) {
                                    $reasons = [];
                                    if (! empty($r->no_tt)) {
                                        $reasons[] = '<span class="text-warning-600 dark:text-warning-400">Tukar Tambah: ' . e($r->no_tt) . '</span>';
                                    }
                                    $notas = $r->getBlockedPenjualanReferences()->pluck('nota')->filter()->implode(', ');
                                    if ($notas) {
                                        $reasons[] = '<span class="text-danger-600 dark:text-danger-400">Penjualan: ' . e($notas) . '</span>';
                                    }
                                    $html .= '<li>'
                                        . '<span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">' . e($r->no_po) . '</span>';
                                    if ($reasons) {
                                        $html .= '<ul class="mt-1 pl-4 space-y-0.5">';
                                        foreach ($reasons as $reason) {
                                            $html .= '<li class="text-xs">— ' . $reason . '</li>';
                                        }
                                        $html .= '</ul>';
                                    }
                                    $html .= '</li>';
                                }
                                $html .= '</ul>';
                                $html .= '</div>';
                            }

                            if ($canDelete->isEmpty()) {
                                $html .= '<p class="text-center text-gray-500 dark:text-gray-400 text-xs pt-1">Tidak ada pembelian yang bisa dihapus dari pilihan ini.</p>';
                            }

                            $html .= '<div class="mt-4 p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg border border-warning-200 dark:border-warning-800">';
                            $html .= '<p class="text-warning-800 dark:text-warning-200 text-xs"><strong>⚠️ Perhatian:</strong> Stok akan dikembalikan dan data terkait (StockBatch, StockMutation) akan dihapus. Tindakan ini tidak dapat dibatalkan.</p>';
                            $html .= '</div>';

                            $html .= '</div>';

                            return new \Illuminate\Support\HtmlString($html);
                        })
                        ->form([
                            \Filament\Forms\Components\TextInput::make('password')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->required()
                                ->placeholder('Masukkan password akun Anda'),
                        ])
                        ->modalSubmitActionLabel('🔥 Hapus yang Bisa Dihapus')
                        ->modalCancelActionLabel('Batal')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $user = auth()->user();

                            if (! $user || ! \Illuminate\Support\Facades\Hash::check($data['password'], $user->password)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Password salah')
                                    ->body('Password yang Anda masukkan tidak sesuai.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $deleted = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (! $record->canDelete()) {
                                    $skipped++;
                                    continue;
                                }

                                try {
                                    $record->delete();
                                    $deleted++;
                                } catch (\Exception $e) {
                                    $skipped++;
                                }
                            }

                            $body = "Berhasil menghapus {$deleted} pembelian.";
                            if ($skipped > 0) {
                                $body .= " {$skipped} dilewati karena tidak bisa dihapus.";
                            }

                            \Filament\Notifications\Notification::make()
                                ->title($deleted > 0 ? 'Hapus Selesai' : 'Tidak Ada yang Dihapus')
                                ->body($body)
                                ->color($deleted > 0 ? 'success' : 'warning')
                                ->send();
                        }),
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

    /*
     * [DEPRECATED - TIDAK DIGUNAKAN]
     * Fungsi ini sebelumnya digunakan untuk auto-fill HPP dan Harga Jual
     * berdasarkan harga terakhir pembelian produk.
     * 
     * Sekarang dihapus dari form karena Purchase Module Policy v1.1
     * mengharuskan input manual untuk fleksibilitas harga.
     * 
     * Dibiarkan di sini untuk referensi jika diperlukan di masa depan.
     * 
     * protected static function getLastRecordedPricingForProduct(int $productId): array
     * {
     *     if ($productId < 1) {
     *         return ['hpp' => null, 'harga_jual' => null];
     *     }
     *
     *     $itemTable = (new PembelianItem)->getTable();
     *     $purchaseTable = (new Pembelian)->getTable();
     *     $productColumn = PembelianItem::productForeignKey();
     *     $primaryKey = PembelianItem::primaryKeyColumn();
     *
     *     $lastItem = PembelianItem::query()
     *         ->leftJoin($purchaseTable, $purchaseTable . '.id_pembelian', '=', $itemTable . '.id_pembelian')
     *         ->where($itemTable . '.' . $productColumn, $productId)
     *         ->orderByDesc($purchaseTable . '.tanggal')
     *         ->orderByDesc($itemTable . '.' . $primaryKey)
     *         ->select([
     *             $itemTable . '.' . $primaryKey,
     *             $itemTable . '.hpp',
     *             $itemTable . '.harga_jual',
     *         ])
     *         ->first();
     *
     *     if (! $lastItem) {
     *         return ['hpp' => null, 'harga_jual' => null];
     *     }
     *
     *     $hpp = $lastItem->hpp;
     *     $hargaJual = $lastItem->harga_jual;
     *
     *     return [
     *         'hpp' => is_null($hpp) ? null : (int) $hpp,
     *         'harga_jual' => is_null($hargaJual) ? null : (int) $hargaJual,
     *     ];
     * }
     */
}
