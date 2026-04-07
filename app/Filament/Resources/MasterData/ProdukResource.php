<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\MasterData\ProdukResource\Pages;
use App\Models\Produk;
// use Filament\Forms\Components\Fieldset;
use App\Support\WebpUpload;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
// use Filament\Resources\Set;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
// use Laravel\Pail\File;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontFamily;
use Filament\Tables; // Import Str
use Filament\Tables\Columns\ImageColumn; // Import Closure for callable type hint
use Filament\Tables\Columns\Layout\Split as TableSplit;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

// use Laravel\SerializableClosure\Serializers\Native;

class ProdukResource extends BaseResource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'hugeicons-package';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationParentItem = 'Produk & Kategori';

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_produk';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_produk', 'sku'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Membagi layar menjadi 3 bagian grid
            ->schema([
                // === KOLOM KIRI (UTAMA - 2 Bagian) ===
                Group::make()
                    ->columnSpan(['lg' => 2]) // Memakan 2 grid di layar besar
                    ->schema([

                        // Section 1: Informasi Dasar
                        Section::make('Informasi Produk')
                            ->description('Masukan nama dan deskripsi lengkap produk.')
                            ->icon('heroicon-m-shopping-bag') // Icon pemanis
                            ->schema([
                                Forms\Components\TextInput::make('nama_produk')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->live(onBlur: true) // Agar slug update realtime (opsional)
                                    ->columnSpanFull(), // Full width agar rapi

                                Forms\Components\RichEditor::make('deskripsi')
                                    ->label('Deskripsi Lengkap')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'bulletList',
                                        'orderedList',
                                        'link',
                                        'h2',
                                        'h3',
                                    ]) // Toolbar minimalis agar clean
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Dimensi & Pengiriman (Pindah kesini agar flow lebih enak)
                        Section::make('Dimensi & Berat')
                            ->icon('heroicon-m-truck')
                            ->columns(2) // Grid 2 kolom di dalam section ini
                            ->schema([
                                Forms\Components\TextInput::make('berat')
                                    ->label('Berat')
                                    ->suffix('gram') // UX: Satuan langsung di input
                                    ->numeric()
                                    ->minValue(0),

                                Forms\Components\Grid::make(3) // Grid 3 untuk P x L x T
                                    ->schema([
                                        Forms\Components\TextInput::make('panjang')
                                            ->label('Panjang')
                                            ->suffix('cm')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('lebar')
                                            ->label('Lebar')
                                            ->suffix('cm')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('tinggi')
                                            ->label('Tinggi')
                                            ->suffix('cm')
                                            ->numeric(),
                                    ])->columnSpan(1),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR - 1 Bagian) ===
                Group::make()
                    ->columnSpan(['lg' => 1]) // Memakan 1 grid sisa
                    ->schema([

                        // Section 3: Gambar (Di sidebar agar proporsional)
                        Section::make('Media')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Foto Produk')
                                    ->image()
                                    ->imageEditor() // Fitur crop bawaan filament
                                    ->disk('public')
                                    ->directory('produks/' . now()->format('Y/m/d'))
                                    ->getUploadedFileNameForStorageUsing(
                                        fn(TemporaryUploadedFile $file, Get $get) => (now()->format('ymd') . '-' . Str::slug($get('nama_produk') ?? 'produk') . '.' . $file->getClientOriginalExtension())
                                    )
                                    ->saveUploadedFileUsing(fn(BaseFileUpload $component, TemporaryUploadedFile $file): ?string => WebpUpload::store($component, $file))
                                    ->openable()
                                    ->downloadable(),
                            ]),

                        // Section 4: Organisasi & Identitas
                        Section::make('Organisasi')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Kode Stok)')
                                    ->placeholder('Otomatis generated setelah pilih Kategori & Brand')
                                    ->dehydrated()
                                    ->readOnly() // Lebih aman readonly daripada disabled jika masih mau disubmit
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Forms\Components\Select::make('kategori_id')
                                    ->relationship('kategori', 'nama_kategori')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $sku = Produk::calculateSmartSku($get('kategori_id'), $get('brand_id'));
                                        if ($sku) $set('sku', $sku);
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama_kategori')->required(),
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'nama_brand')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $sku = Produk::calculateSmartSku($get('kategori_id'), $get('brand_id'));
                                        if ($sku) $set('sku', $sku);
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama_brand')->required(),
                                    ])
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Grid utama 3 kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Informasi Dasar
                        InfolistSection::make('Detail Produk')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                TextEntry::make('nama_produk')
                                    ->label('Nama Produk')
                                    ->weight('bold')
                                    ->size(TextEntrySize::Large)
                                    ->columnSpanFull(),
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('sn')
                                            ->label('SN')
                                            ->placeholder('-'),
                                        TextEntry::make('garansi')
                                            ->label('Garansi')
                                            ->placeholder('-'),
                                    ]),

                                TextEntry::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->html() // Karena pakai RichEditor di form
                                    ->prose() // Agar styling list/bold nya rapi
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Fisik & Logistik (Disini kita hitung Volume)
                        InfolistSection::make('Dimensi & Berat')
                            ->icon('heroicon-m-cube')
                            ->schema([
                                InfolistGrid::make(3) // Baris 1: Berat Asli & Berat Volume
                                    ->schema([
                                        TextEntry::make('berat')
                                            ->label('Berat Fisik')
                                            ->suffix(' gram')
                                            ->icon('heroicon-m-scale'),

                                        // --- INI CARA HITUNGNYA ---
                                        TextEntry::make('berat_volume')
                                            ->label('Berat Volume')
                                            ->state(function (Produk $record) {
                                                // Rumus: (P x L x T) / 4000
                                                // Asumsi input P,L,T dalam cm. Hasil biasanya dalam Kg atau Gram tergantung kurir.
                                                // Umumnya rumus dibagi 4000/6000 menghasilkan Kg.
                                                // Mari kita anggap hasilnya Kg.

                                                $p = $record->panjang ?? 0;
                                                $l = $record->lebar ?? 0;
                                                $t = $record->tinggi ?? 0;

                                                if ($p == 0 || $l == 0 || $t == 0) {
                                                    return '-';
                                                }

                                                $volumetric = ($p * $l * $t) / 4000;

                                                return number_format($volumetric, 2) . ' Kg';
                                            })
                                            ->icon('heroicon-m-calculator')
                                            ->color('warning') // Pembeda visual bahwa ini hitungan sistem
                                            ->helperText('(P x L x T) / 4000'),
                                    ]),

                                InfolistGrid::make(3) // Baris 2: Detail Dimensi
                                    ->schema([
                                        TextEntry::make('panjang')
                                            ->label('Panjang')
                                            ->suffix(' cm'),
                                        TextEntry::make('lebar')
                                            ->label('Lebar')
                                            ->suffix(' cm'),
                                        TextEntry::make('tinggi')
                                            ->label('Tinggi')
                                            ->suffix(' cm'),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Gambar
                        InfolistSection::make('Visual')
                            ->schema([
                                ImageEntry::make('image_url')
                                    ->label('')
                                    ->disk('public')
                                    ->height(200) // Batasi tinggi agar tidak terlalu besar
                                    ->extraImgAttributes([
                                        'class' => 'object-contain rounded-lg shadow-sm', // Tailwind classes
                                        'alt' => 'Foto Produk',
                                    ]),
                            ]),

                        // Section 4: Organisasi
                        InfolistSection::make('Identitas')
                            ->icon('heroicon-m-tag')
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->copyable() // Fitur copy SKU berguna banget buat admin
                                    ->fontFamily(FontFamily::Mono), // Font monospace ala kode

                                TextEntry::make('kategori.nama_kategori')
                                    ->label('Kategori')
                                    ->badge() // Tampil sebagai badge warna
                                    ->color('info'),

                                TextEntry::make('brand.nama_brand')
                                    ->label('Brand')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TableSplit::make([
                    ImageColumn::make('image_url')
                        ->label('')
                        ->disk('public')
                        ->height(128)
                        ->width(128)
                        ->square()
                        ->defaultImageUrl(url('/images/icons/icon-256x256.png'))
                        ->extraImgAttributes([
                            'class' => 'rounded-lg border border-gray-200/60 object-cover shadow-sm',
                            'alt' => 'Foto Produk',
                        ]),
                    Stack::make([
                        TextColumn::make('nama_produk')
                            ->label('Produk')
                            ->weight('bold')
                            ->formatStateUsing(fn($state) => Str::upper($state))
                            ->size(TextColumnSize::Large)
                            ->description(fn(Produk $record) => new HtmlString('<span class="font-mono">SKU: ' . e($record->sku ?? '-') . '</span>'))
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('kategori.nama_kategori')
                            ->label('Kategori')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-tag')
                            ->formatStateUsing(fn($state) => Str::title($state))
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('brand.nama_brand')
                            ->label('Brand')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-m-building-office-2')
                            ->formatStateUsing(fn($state) => Str::title($state))
                            ->searchable()
                            ->sortable(),
                    ])->space(2),
                ])->from('md'),
            ])
            ->filters([
                //
                SelectFilter::make('kategori')->relationship('kategori', 'nama_kategori')->native(false),
                SelectFilter::make('brand')->relationship('brand', 'nama_brand')->native(false),
                TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make()
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->button()
                    ->color('success'),
                Tables\Actions\ForceDeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->button()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('success')
                        ->requiresConfirmation()
                        ->label('Pulihkan Data'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->label('Hapus Selamanya'),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'view' => Pages\ViewProduk::route('/{record}'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
