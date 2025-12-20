<?php

namespace App\Filament\Resources;

use App\Models\Produk;
use App\Models\PembelianItem;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RequestOrder;
use App\Models\Kategori;
use Filament\Resources\Resource;
use Filament\Forms\Components\Split as FormsSplit;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\RequestOrderResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Grid;
use Filament\Support\Enums\FontWeight;

class RequestOrderResource extends Resource
{
    protected static ?string $model = RequestOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $recordTitleAttribute = 'no_ro';
    protected static ?string $pluralLabel = 'Request Order';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === BAGIAN ATAS: DETAIL PERMINTAAN & CATATAN ===
                FormsSplit::make([
                    // Kolom Kiri: Detail Permintaan
                    Section::make('Detail Permintaan')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            TextInput::make('no_ro')
                                ->label('Nomor RO')
                                ->required()
                                ->default(fn () => RequestOrder::generateRO())
                                ->disabled()
                                ->dehydrated()
                                ->unique(ignoreRecord: true),

                            Select::make('karyawan_id')
                                ->label('Karyawan')
                                ->relationship('karyawan', 'nama_karyawan')
                                ->searchable()
                                ->preload()
                                ->default(fn (Get $get) => auth()->user()->karyawan?->id)
                                ->native(false)
                                ->required(),

                            DatePicker::make('tanggal')
                                ->label('Tanggal Permintaan')
                                ->required()
                                ->native(false)
                                ->default(now()),
                        ]),

                    // Kolom Kanan: Catatan
                    Section::make('Catatan Tambahan')
                        ->icon('heroicon-m-pencil-square')
                        ->schema([
                            RichEditor::make('catatan')
                                ->label('') // Label kosong agar editor lebih luas
                                ->toolbarButtons([
                                    'bold', 'italic', 'bulletList', 'orderedList'
                                ]) // Toolbar minimalis agar tidak penuh
                                ->columnSpanFull(),
                        ])
                        ->grow(true), // Agar section ini mengisi sisa ruang
                ])
                ->from('md') // Split hanya aktif di layar medium ke atas
                ->columnSpanFull(),

                // === BAGIAN BAWAH: DAFTAR PRODUK (REPEATER) ===
                Section::make('Daftar Produk')
                    ->description('Masukan daftar barang yang diminta.')
                    ->schema([
                        TableRepeater::make('items')
                            ->label('') // Label kosong agar header section yang bicara
                            ->relationship('items')
	                            ->minItems(1)
	                            ->reorderable(false)
	                            ->cloneable()
	                            ->schema([
	                                Select::make('kategori_id')
	                                    ->label('Kategori')
	                                    ->options(fn () => Kategori::query()
	                                        ->orderBy('nama_kategori')
	                                        ->pluck('nama_kategori', 'id')
	                                        ->all())
	                                    ->searchable()
	                                    ->required()
	                                    ->native(false)
	                                    ->dehydrated(false)
	                                    ->reactive()
	                                    ->afterStateUpdated(function (Set $set): void {
	                                        $set('produk_id', null);
	                                        $set('hpp', null);
	                                        $set('harga_jual', null);
	                                    })
	                                    ->columnSpan(1),

	                                Select::make('produk_id')
	                                    ->label('Produk')
	                                    ->placeholder('Select an option')
	                                    ->options(function (Get $get): array {
	                                        $kategoriId = $get('kategori_id');

	                                        if (! $kategoriId) {
	                                            return [];
	                                        }

	                                        return Produk::query()
	                                            ->where('kategori_id', $kategoriId)
	                                            ->orderBy('nama_produk')
	                                            ->pluck('nama_produk', 'id')
	                                            ->all();
	                                    })
	                                    ->searchable()
	                                    ->required()
	                                    ->native(false)
	                                    ->reactive()
	                                    ->disabled(fn (Get $get) => ! $get('kategori_id'))
	                                    ->afterStateUpdated(function ($state, Set $set): void {
	                                        if (! $state) {
	                                            $set('hpp', null);
	                                            $set('harga_jual', null);
	                                            return;
	                                        }

	                                        $pricing = self::getLatestPricing((int) $state);
	                                        $set('hpp', $pricing['hpp']);
	                                        $set('harga_jual', $pricing['harga_jual']);
	                                    })
	                                    ->afterStateHydrated(function ($state, Set $set, Get $get): void {
	                                        if (! $state || $get('kategori_id')) {
	                                            return;
	                                        }

	                                        $set('kategori_id', Produk::query()->whereKey($state)->value('kategori_id'));

	                                        $pricing = self::getLatestPricing((int) $state);
	                                        $set('hpp', $pricing['hpp']);
	                                        $set('harga_jual', $pricing['harga_jual']);
	                                    })
	                                    ->columnSpan(2), // Produk lebih lebar

	                                TextInput::make('hpp')
	                                    ->label('HPP')
	                                    ->prefix('Rp')
	                                    ->numeric()
	                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
	                                    ->disabled()
	                                    ->dehydrated(false)
	                                    ->columnSpan(1),

	                                TextInput::make('harga_jual')
	                                    ->label('Harga Jual')
	                                    ->prefix('Rp')
	                                    ->numeric()
	                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
	                                    ->disabled()
	                                    ->dehydrated(false)
	                                    ->columnSpan(1),
	                            ])
	                            ->colStyles([
	                                // Opsional: Jika plugin mendukung colStyles untuk mengatur lebar kolom
	                                'kategori_id' => 'width: 25%;',
	                                'produk_id' => 'width: 35%;',
	                                'hpp' => 'width: 20%;',
	                                'harga_jual' => 'width: 20%;',
	                            ]),
	                    ]),
	            ]);
	    }

	    protected static function getLatestPricing(?int $productId): array
	    {
	        if (! $productId) {
	            return ['hpp' => null, 'harga_jual' => null];
	        }

	        $qtySisaColumn = PembelianItem::qtySisaColumn();
	        $productColumn = PembelianItem::productForeignKey();

	        $latestBatch = PembelianItem::query()
	            ->where($productColumn, $productId)
	            ->where($qtySisaColumn, '>', 0)
	            ->orderByDesc(PembelianItem::primaryKeyColumn())
	            ->first();

	        return [
	            'hpp' => $latestBatch?->hpp,
	            'harga_jual' => $latestBatch?->harga_jual,
	        ];
	    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Layout Grid 3 Kolom
            ->schema([

                // === KOLOM KIRI (DAFTAR PRODUK) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        
	                        InfolistSection::make('Rincian Permintaan')
	                            ->icon('heroicon-m-shopping-bag')
	                            ->schema([
	                                ViewEntry::make('items_table')
	                                    ->hiddenLabel()
	                                    ->view('filament.infolists.components.request-order-items-table')
	                                    ->state(fn (RequestOrder $record) => $record->items()->with(['produk.brand', 'produk.kategori'])->get()),
	                            ]),
	                    ]),

                // === KOLOM KANAN (SIDEBAR INFO) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 1: Header Dokumen
                        InfolistSection::make('Info Dokumen')
                            ->schema([
                                TextEntry::make('no_ro')
                                    ->label('Nomor RO')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-document-text')
                                    ->copyable(),

                                TextEntry::make('tanggal')
                                    ->label('Tanggal Request')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days'),
                            ]),

                        // Section 2: Pemohon
                        InfolistSection::make('Pemohon')
                            ->schema([
                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Nama Karyawan')
                                    ->weight(FontWeight::Medium)
                                    ->icon('heroicon-m-user-circle')
                                    ->color('primary'),

                                // Asumsi ada relasi jabatan/posisi di karyawan
                                // Jika tidak ada, bisa dihapus
                                TextEntry::make('karyawan.jabatan') 
                                    ->label('Jabatan')
                                    ->icon('heroicon-m-briefcase')
                                    ->placeholder('-')
                                    ->size(TextEntrySize::Small)
                                    ->color('gray'),
                            ]),

                        // Section 3: Catatan
                        InfolistSection::make('Catatan')
                            ->schema([
                                TextEntry::make('catatan')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->html() // Karena form pakai RichEditor
                                    ->prose() // Styling typography yang rapi
                                    ->placeholder('Tidak ada catatan tambahan.')
                                    ->columnSpanFull(),
                            ])
                            ->grow(true), // Agar mengisi ruang kosong ke bawah
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ro')
                    ->label('No. RO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Produk')
                    ->counts('items')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('karyawan_id')
                    ->label('Karyawan')
                    ->relationship('karyawan', 'nama_karyawan'),
            ])
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
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestOrders::route('/'),
            'create' => Pages\CreateRequestOrder::route('/create'),
            'view' => Pages\ViewRequestOrder::route('/{record}'),
            'edit' => Pages\EditRequestOrder::route('/{record}/edit'),
        ];
    }

}
