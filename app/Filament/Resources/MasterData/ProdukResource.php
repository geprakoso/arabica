<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\ProdukResource\Pages;
use App\Filament\Exports\ProdukExporter;
use App\Models\Produk;
use Filament\Forms;
// use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
// use Filament\Resources\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
// use Laravel\Pail\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str; // Import Str
use Closure; // Import Closure for callable type hint
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Actions\ExportAction;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontFamily;


// use Laravel\SerializableClosure\Serializers\Native;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'hugeicons-package';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'Produk & Jasa';
    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationLabel = 'Produk';
    protected static ?string $pluralModelLabel = 'Produk';
    protected static ?int $navigationSort = 1;

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
                                        'bold', 'italic', 'bulletList', 'orderedList', 'link', 'h2', 'h3'
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
                                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file, Get $get) => 
                                        (now()->format('ymd') . '-' . Str::slug($get('nama_produk') ?? 'produk') . '.' . $file->getClientOriginalExtension())
                                    )
                                    ->openable()
                                    ->downloadable(),
                            ]),

                        // Section 4: Organisasi & Identitas
                        Section::make('Organisasi')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Kode Stok)')
                                    ->default(fn () => Produk::generateSku())
                                    ->dehydrated()
                                    ->readOnly() // Lebih aman readonly daripada disabled jika masih mau disubmit
                                    ->required()
                                    ->unique(ignoreRecord: true),

                                Forms\Components\Select::make('kategori_id')
                                    ->relationship('kategori', 'nama_kategori')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama_kategori')->required(),
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('brand_id')
                                    ->relationship('brand', 'nama_brand')
                                    ->searchable()
                                    ->preload()
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
                                                
                                                if($p == 0 || $l == 0 || $t == 0) return '-';

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
                //
                TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.nama_brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
                SelectFilter::make('kategori')->relationship('kategori', 'nama_kategori')->native(false),
                SelectFilter::make('brand')->relationship('brand', 'nama_brand')->native(false),
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
