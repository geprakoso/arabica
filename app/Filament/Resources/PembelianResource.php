<?php

namespace App\Filament\Resources;

use App\Models\Pembelian;
use App\Models\RequestOrder;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker as FormsDatePicker;
use App\Filament\Resources\PembelianResource\Pages;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Split;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Pembelian';

    protected static ?string $pluralLabel = 'Pembelian';
    
    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

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
                                    ->label('Nomor PO')
                                    ->default(fn () => Pembelian::generatePO())
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
                                    ->required()
                                    ->native(false),

                                Select::make('id_karyawan')
                                    ->label('PIC / Karyawan')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
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
                                    ->native(false)
                                    ->required(),
                            ]),
                        ]),
                    ]),

                // === BAGIAN 2: DAFTAR BARANG (REPEATER) ===
                FormsSection::make('Item Barang')
                    ->headerActions([
                        // Opsional: Tombol aksi di header section jika dibutuhkan
                    ])
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->hiddenLabel() // Hilangkan label "Items" agar lebih clean
                            ->minItems(1)
                            ->columns(12) // Menggunakan grid 12 kolom agar presisi
                            ->schema([
                                Select::make('id_produk')
                                    ->label('Produk')
                                    ->relationship('produk', 'nama_produk')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems() // Mencegah duplikasi produk
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
                                    ->required()
                                    ->columnSpan([
                                        'md' => 2,
                                        'xl' => 2,
                                    ]),

                                TextInput::make('harga_jual')
                                    ->label('Jual')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required()
                                    ->columnSpan([
                                        'md' => 3, // Sisa kolom
                                        'xl' => 3,
                                    ]),
                            ])
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['id_produk'] ?? null ? 'Produk Terpilih' : null),
                    ]),

                // === BAGIAN 3: PEMBAYARAN & CATATAN (Footer Layout) ===
                FormsSection::make()
                    ->schema([
                        FormsGrid::make(2)->schema([
                            // Kolom Kiri: Catatan
                            FormsGroup::make()->schema([
                                Textarea::make('catatan')
                                    ->label('Catatan / Keterangan')
                                    ->rows(3)
                                    ->placeholder('Catatan tambahan...'),
                            ]),

                            // Kolom Kanan: Termin Pembayaran
                            FormsGroup::make()->schema([
                                FormsGrid::make(2)->schema([
                                    Select::make('jenis_pembayaran')
                                        ->label('Metode Bayar')
                                        ->options([
                                            'lunas' => 'Lunas (Cash/Transfer)',
                                            'tempo' => 'Tempo (Hutang)',
                                        ])
                                        ->default('lunas')
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, $state) => $state !== 'tempo' ? $set('tgl_tempo', null) : null)
                                        ->native(false)
                                        ->required()
                                        ->columnSpan(fn (Get $get) => $get('jenis_pembayaran') === 'tempo' ? 1 : 2),

                                    FormsDatePicker::make('tgl_tempo')
                                    ->label('Jatuh Tempo')
                                    ->visible(fn (Get $get) => $get('jenis_pembayaran') === 'tempo')
                                    ->required(fn (Get $get) => $get('jenis_pembayaran') === 'tempo')
                                    ->native(false),
                                ]),
                            ]),
                        ]),
                    ]),
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
                            ]),

                            // Kanan: Status & Pembayaran
                            InfoGroup::make([
                                TextEntry::make('jenis_pembayaran')
                                    ->label('Pembayaran')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'lunas' ? 'success' : 'warning')
                                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                                TextEntry::make('tipe_pembelian')
                                    ->label('Tipe Pajak')
                                    ->badge()
                                    ->color('gray')
                                    ->formatStateUsing(fn (string $state): string => $state === 'ppn' ? 'PPN' : 'Non-PPN'),
                            ])->grow(false), // Agar kolom kanan tidak terlalu lebar
                        ])->from('md'), // Split hanya aktif di layar medium ke atas
                    ]),

                // === BAGIAN TENGAH: TABEL BARANG (CLEAN TABLE) ===
                InfoSection::make('Daftar Barang')
                    ->compact() // Mengurangi padding agar lebih rapat
                    ->schema([
                        // 1. HEADER TABEL (Manual Grid)
                        InfoGrid::make(12)
                            ->extraAttributes(['class' => 'pb-2 border-b border-gray-200 dark:border-gray-700 font-bold text-sm text-gray-500'])
                            ->schema([
                                TextEntry::make('h_prod')->default('PRODUK')->label('')->columnSpan(5),
                                TextEntry::make('h_cond')->default('KONDISI')->label('')->columnSpan(2)->alignCenter(),
                                TextEntry::make('h_qty')->default('QTY')->label('')->columnSpan(1)->alignCenter(),
                                TextEntry::make('h_hpp')->default('HARGA BELI')->label('')->columnSpan(2)->alignRight(),
                                TextEntry::make('h_sell')->default('HARGA JUAL')->label('')->columnSpan(2)->alignRight(),
                            ]),

                        // 2. ISI TABEL (Repeatable Entry)
                        RepeatableEntry::make('items')
                            ->label('')
                            ->contained(false) // KUNCI AGAR TIDAK KOTAK-KOTAK
                            ->schema([
                                InfoGrid::make(12)
                                    ->extraAttributes(['class' => 'py-3 border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 transition']) 
                                    ->schema([
                                        
                                        // Produk
                                        InfoGroup::make([
                                            TextEntry::make('produk.nama_produk')
                                                ->hiddenLabel()
                                                ->weight(FontWeight::SemiBold),
                                        ])->columnSpan(5),

                                        // Kondisi
                                        TextEntry::make('kondisi')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->alignCenter()
                                            ->color(fn (string $state): string => $state === 'baru' ? 'success' : 'warning')
                                            ->columnSpan(2),

                                        // Qty
                                        TextEntry::make('qty')
                                            ->hiddenLabel()
                                            ->alignCenter()
                                            ->columnSpan(1),

                                        // HPP
                                        TextEntry::make('hpp')
                                            ->hiddenLabel()
                                            ->money('IDR')
                                            ->alignRight()
                                            ->color('gray')
                                            ->columnSpan(2),

                                        // Harga Jual
                                        TextEntry::make('harga_jual')
                                            ->hiddenLabel()
                                            ->money('IDR')
                                            ->alignRight()
                                            ->color('primary')
                                            ->weight(FontWeight::Medium)
                                            ->columnSpan(2),
                                    ]),
                            ]),
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

                                    TextEntry::make('catatan')
                                        ->label('Catatan Tambahan')
                                        ->markdown()
                                        ->prose()
                                        ->placeholder('Tidak ada catatan'),
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
                                        ->visible(fn ($record) => $record->jenis_pembayaran === 'tempo')
                                        ->alignRight(),
                                ])->visible(fn ($record) => $record->jenis_pembayaran === 'tempo'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('requestOrders'))
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('request_orders_label')
                    ->label('Request Order')
                    ->state(fn (Pembelian $record) => $record->requestOrders
                        ->map(fn ($ro) => '#'.$ro->no_ro)
                        ->implode(', ')) // semua Request Order yang terkait → ambil no_ro → kasih # di depan → gabung jadi satu teks dipisah koma.
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('tipe_pembelian')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null), // Ubah text ke format uppercase dan ganti underscore dengan spasi
                TextColumn::make('jenis_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null) // Ubah text ke format uppercase dan ganti underscore dengan spasi
                    ->colors([
                        'success' => 'lunas',
                        'warning' => 'tempo',
                    ]),
                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Pembelian')
                    ->icon('heroicon-s-plus'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($ids->isEmpty()) {
            return null;
        }

        $tags = RequestOrder::query()
            ->whereIn('id', $ids)
            ->pluck('no_ro')
            ->filter()
            ->map(fn ($noRo) => "#{$noRo}")
            ->toArray();

        return empty($tags) ? null : implode(', ', $tags);
    }

}
