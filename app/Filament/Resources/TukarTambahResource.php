<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TukarTambahResource\Pages;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PembelianRelationManager;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PenjualanRelationManager;
use App\Models\AkunTransaksi;
use App\Models\Jasa;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\Supplier;
use App\Models\TukarTambah;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

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
                Section::make('📋 Informasi Tukar Tambah')
                    ->description('Informasi umum transaksi tukar tambah barang lama dengan barang baru')
                    ->schema([
                        TextInput::make('no_nota')
                            ->label('No. Nota')
                            ->default(fn () => TukarTambah::generateNoNota())
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Nomor nota akan digenerate otomatis'),
                        DatePicker::make('tanggal')
                            ->label('Tanggal Transaksi')
                            ->default(now())
                            ->displayFormat('d F Y')
                            ->native(false)
                            ->required()
                            ->prefixIcon('heroicon-o-calendar'),
                        Select::make('id_karyawan')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->karyawan?->id)
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Karyawan yang menangani transaksi ini')
                            ->columnSpanFull(),
                        Section::make()
                            ->heading('📝 Catatan Tambahan')
                            ->schema([
                                Textarea::make('catatan')
                                    ->label('Catatan')
                                    ->rows(3)
                                    ->nullable()
                                    ->placeholder('Tambahkan catatan atau keterangan khusus untuk transaksi ini...')
                                    ->helperText('Catatan bersifat opsional'),
                            ])
                            ->collapsible()
                            ->collapsed(true)
                            ->compact()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Tabs::make('Transaksi')
                    ->tabs([
                        Tab::make('Penjualan')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Section::make('Informasi Penjualan')
                                    ->description('Data penjualan barang baru kepada pelanggan')
                                    ->icon('heroicon-o-receipt-percent')
                                    ->schema([
                                        TextInput::make('no_nota')
                                            ->label('No. Nota Penjualan')
                                            ->default(fn () => Penjualan::generateNoNota())
                                            ->disabled()
                                            ->dehydrated()
                                            ->required()
                                            ->prefixIcon('heroicon-o-hashtag'),
                                        Select::make('id_member')
                                            ->label('Pelanggan / Member')
                                            ->options(fn () => Member::query()->orderBy('nama_member')->pluck('nama_member', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->placeholder('Pilih pelanggan atau kosongkan untuk umum'),
                                        Select::make('id_karyawan')
                                            ->label('Petugas Penjualan')
                                            ->options(fn () => \App\Models\Karyawan::query()->orderBy('nama_karyawan')->pluck('nama_karyawan', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user'),
                                        Section::make()
                                            ->heading('📝 Catatan')
                                            ->schema([
                                                Textarea::make('catatan')
                                                    ->label('Catatan Penjualan')
                                                    ->rows(2)
                                                    ->nullable()
                                                    ->placeholder('Catatan tambahan untuk penjualan ini...'),
                                            ])
                                            ->collapsible()
                                            ->collapsed(true)
                                            ->compact()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                                Section::make('Produk yang Dijual')
                                    ->description('Daftar produk/barang baru yang dijual kepada pelanggan')
                                    ->icon('heroicon-o-cube')
                                    ->schema([
                                        TableRepeater::make('items')
                                            ->label('')
                                            ->minItems(1)
                                            ->addActionLabel('+ Tambah Produk')
                                            ->schema([
                                                Select::make('id_produk')
                                                    ->label('Produk')
                                                    ->options(fn () => \App\Filament\Resources\PenjualanResource::getAvailableProductOptions())
                                                    ->searchable()
                                                    ->placeholder('Pilih Produk')
                                                    ->preload()
                                                    ->required()
                                                    ->native(false),
                                                Select::make('kondisi')
                                                    ->label('Kondisi')
                                                    ->options([
                                                        'baru' => 'Baru',
                                                        'bekas' => 'Bekas',
                                                    ])
                                                    ->native(false)
                                                    ->placeholder('Pilih Kondisi')
                                                    ->nullable(),
                                                TextInput::make('qty')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required(),
                                                TextInput::make('harga_jual')
                                                    ->label('Harga Jual')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->minValue(0)
                                                    ->nullable(),
                                                Repeater::make('serials')
                                                    ->label('Serial Number & Garansi')
                                                    ->schema([
                                                        TextInput::make('sn')
                                                            ->label('Serial Number')
                                                            ->placeholder('Masukkan nomor seri')
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                        TextInput::make('garansi')
                                                            ->label('Garansi')
                                                            ->placeholder('Contoh: 1 Tahun, 6 Bulan, dst.')
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                    ])
                                                    ->columns(2)
                                                    ->defaultItems(0)
                                                    ->maxItems(1)
                                                    ->addActionLabel('+ Tambah Serial Number')
                                                    ->collapsible()
                                                    ->deletable(false)
                                                    ->collapsed()
                                                    ->itemLabel(fn (array $state): ?string => $state['sn'] ?? 'Serial Number')
                                                    ->reorderableWithButtons(false)
                                                    ->reorderable(false)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(2)
                                            ->reorderableWithButtons(false)
                                            ->reorderable(false),
                                    ])
                                    ->collapsible(),
                                Section::make('Diskon & Pembayaran')
                                    ->description('Informasi diskon dan metode pembayaran (bisa split payment)')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        TextInput::make('diskon_total')
                                            ->label('Total Diskon')
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->default(0)
                                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                            ->prefixIcon('heroicon-o-receipt-percent')
                                            ->helperText('Diskon yang diberikan untuk transaksi ini'),
                                        TableRepeater::make('pembayaran')
                                            ->label('Detail Pembayaran')
                                            ->minItems(0)
                                            ->addActionLabel('+ Tambah Metode Pembayaran')
                                            ->helperText('Anda dapat menambahkan beberapa metode pembayaran (split payment)')
                                            ->schema([
                                                Select::make('metode_bayar')
                                                    ->label('Metode')
                                                    ->options([
                                                        'cash' => 'Tunai',
                                                        'transfer' => 'Transfer',
                                                    ])
                                                    ->native(false)
                                                    ->placeholder('Pilih Pembayaran')
                                                    ->required()
                                                    ->reactive(),
                                                Select::make('akun_transaksi_id')
                                                    ->label('Akun Transaksi')
                                                    ->options(fn () => AkunTransaksi::query()
                                                        ->where('is_active', true)
                                                        ->orderBy('nama_akun')
                                                        ->pluck('nama_akun', 'id')
                                                        ->all())
                                                    ->searchable()
                                                    ->placeholder('Pilih Akun')
                                                    ->preload()
                                                    ->native(false)
                                                    ->required(fn (Get $get) => $get('metode_bayar') === 'transfer'),
                                                TextInput::make('jumlah')
                                                    ->label('Jumlah')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required(),
                                                TextInput::make('catatan')
                                                    ->label('Catatan')
                                                    ->maxLength(255)
                                                    ->nullable(),
                                            ])
                                            ->columns(4)
                                            ->reorderable(false)
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true),
                                Section::make('Jasa Tambahan')
                                    ->description('Jasa atau layanan yang ditambahkan dalam penjualan (opsional)')
                                    ->icon('heroicon-o-wrench-screwdriver')
                                    ->schema([
                                        TableRepeater::make('jasa_items')
                                            ->label('')
                                            ->minItems(0)
                                            ->addActionLabel('+ Tambah Jasa')
                                            ->schema([
                                                Select::make('jasa_id')
                                                    ->label('Jasa')
                                                    ->options(fn () => Jasa::query()->orderBy('nama_jasa')->pluck('nama_jasa', 'id')->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Pilih Jasa')
                                                    ->required()
                                                    ->native(false)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $jasa = Jasa::find($state);
                                                            if ($jasa) {
                                                                $set('harga', $jasa->harga);
                                                            }
                                                        }
                                                    }),
                                                TextInput::make('qty')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required(),
                                                TextInput::make('harga')
                                                    ->label('Tarif Jasa')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required(),
                                                Textarea::make('catatan')
                                                    ->label('Catatan')
                                                    ->rows(1)
                                                    ->nullable(),
                                            ])
                                            ->columns(2),
                                    ])
                                    ->collapsible()
                                    ->collapsed(true),
                            ])
                            ->statePath('penjualan'),
                        Tab::make('Pembelian')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Section::make('Informasi Pembelian')
                                    ->description('Data pembelian barang bekas dari pelanggan')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        TextInput::make('no_po')
                                            ->label('No. PO')
                                            ->default(fn () => Pembelian::generatePO())
                                            ->disabled()
                                            ->dehydrated()
                                            ->required()
                                            ->prefixIcon('heroicon-o-hashtag'),
                                        Select::make('id_supplier')
                                            ->label('Supplier')
                                            ->options(fn () => Supplier::query()->orderBy('nama_supplier')->pluck('nama_supplier', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-building-storefront'),
                                        Select::make('id_karyawan')
                                            ->label('Karyawan Pembelian')
                                            ->options(fn () => \App\Models\Karyawan::query()->orderBy('nama_karyawan')->pluck('nama_karyawan', 'id')->all())
                                            ->searchable()
                                            ->preload()
                                            ->nullable()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-user'),
                                        Section::make()
                                            ->heading('📝 Catatan')
                                            ->schema([
                                                Textarea::make('catatan')
                                                    ->label('Catatan Pembelian')
                                                    ->rows(2)
                                                    ->nullable()
                                                    ->placeholder('Catatan tambahan untuk pembelian ini...'),
                                            ])
                                            ->collapsible()
                                            ->collapsed(true)
                                            ->compact()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible(),
                                Section::make('Pengaturan Pembayaran')
                                    ->description('Pajak dan metode pembayaran')
                                    ->icon('heroicon-o-banknotes')
                                    ->schema([
                                        Select::make('tipe_pembelian')
                                            ->label('Pajak')
                                            ->options([
                                                'non_ppn' => 'Non PPN',
                                                'ppn' => 'PPN (11%)',
                                            ])
                                            ->default('non_ppn')
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-receipt-percent'),
                                        Select::make('jenis_pembayaran')
                                            ->label('Metode Bayar')
                                            ->options([
                                                'lunas' => 'Lunas (Cash/Transfer)',
                                                'tempo' => 'Tempo (Hutang)',
                                            ])
                                            ->default('lunas')
                                            ->native(false)
                                            ->reactive()
                                            ->prefixIcon('heroicon-o-credit-card'),
                                        DatePicker::make('tgl_tempo')
                                            ->label('Tanggal Tempo')
                                            ->native(false)
                                            ->visible(fn (Get $get) => $get('jenis_pembayaran') === 'tempo')
                                            ->required(fn (Get $get) => $get('jenis_pembayaran') === 'tempo')
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(true),
                                Section::make('Produk Pembelian')
                                    ->description('Daftar produk yang dibeli')
                                    ->icon('heroicon-o-cube')
                                    ->schema([
                                        TableRepeater::make('items')
                                            ->label('')
                                            ->minItems(1)
                                            ->addActionLabel('+ Tambah Produk')
                                            ->schema([
                                                Select::make('id_produk')
                                                    ->label('Produk')
                                                    ->options(fn () => \App\Models\Produk::query()->orderBy('nama_produk')->pluck('nama_produk', 'id')->all())
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->native(false),
                                                Select::make('kondisi')
                                                    ->label('Kondisi')
                                                    ->options([
                                                        'baru' => 'Baru',
                                                        'bekas' => 'Bekas',
                                                    ])
                                                    ->default('baru')
                                                    ->required()
                                                    ->native(false),
                                                TextInput::make('qty')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required(),
                                                TextInput::make('hpp')
                                                    ->label('HPP (Beli)')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->minValue(0)
                                                    ->required(),
                                                TextInput::make('harga_jual')
                                                    ->label('Harga Jual')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->minValue(0)
                                                    ->required(),
                                            ])
                                            ->columns(2),
                                    ])
                                    ->collapsible(),
                            ])
                            ->statePath('pembelian'),
                    ])
                    ->columnSpanFull()
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_tukar_tambah')
                    ->label('Kode')
                    ->state(fn (TukarTambah $record): string => $record->kode)
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
                    ->url(fn (TukarTambah $record) => route('tukar-tambah.invoice', $record))
                    ->openUrlInNewTab(),
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ]);
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
                                    ->state(fn (TukarTambah $record): string => $record->kode)
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
                                    ->url(fn (TukarTambah $record) => $record->penjualan
                                        ? PenjualanResource::getUrl('edit', ['record' => $record->penjualan])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                                TextEntry::make('pembelian.no_po')
                                    ->label('Nota Pembelian')
                                    ->icon('heroicon-m-document-text')
                                    ->url(fn (TukarTambah $record) => $record->pembelian
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
