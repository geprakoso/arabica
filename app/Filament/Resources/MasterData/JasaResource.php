<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\JasaResource\Pages;
// use App\Filament\Resources\MasterData\JasaResource\RelationManagers;
use App\Models\Jasa;
use Carbon\Carbon;
use Filament\Forms;
// use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str; // Import Str
// use Closure; // Import Closure for callable type hint
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontFamily;

class JasaResource extends Resource
{
    protected static ?string $model = Jasa::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-tools';
    protected static ?string $navigationGroup = 'Master Data';
    // protected static ?string $navigationParentItem = 'Produk & Jasa';
    protected static ?string $pluralModelLabel = 'Jasa';
    protected static ?string $navigationLabel = 'Jasa';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Grid utama 3 kolom
            ->schema([

                // === KOLOM KIRI (Konten Utama) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Informasi Dasar
                        Section::make('Informasi Jasa')
                            ->description('Detail lengkap mengenai layanan jasa yang ditawarkan.')
                            ->icon('heroicon-m-wrench-screwdriver') // Icon pemanis
                            ->schema([
                                Forms\Components\TextInput::make('nama_jasa')
                                    ->label('Nama Jasa')
                                    ->dehydrateStateUsing(fn($state) => Str::title($state))
                                    ->required()
                                    ->placeholder('Contoh: Service AC Split 1PK')
                                    ->unique(ignoreRecord: true)
                                    ->validationMessages([
                                        'unique' => 'Nama jasa sudah terdaftar.',
                                    ])
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('deskripsi')
                                    ->label('Deskripsi Lengkap')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'bulletList',
                                        'orderedList',
                                        'h3',
                                        'undo',
                                        'redo'
                                    ]) // Toolbar minimalis
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Penawaran (Harga & Waktu)
                        Section::make('Penawaran & Estimasi')
                            ->icon('heroicon-m-currency-dollar')
                            ->columns(2) // Grid 2 kolom agar Harga & Waktu berdampingan
                            ->schema([
                                Forms\Components\TextInput::make('harga')
                                    ->label('Biaya Jasa')
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                    ->required(),

                                Forms\Components\TimePicker::make('estimasi_waktu_jam')
                                    ->label('Estimasi Durasi')
                                    ->prefix('Jam')
                                    ->seconds(false)
                                    ->required()
                                    ->datalist([
                                        '01:00',
                                        '02:00',
                                        '03:00',
                                        '04:00',
                                        '05:00',
                                    ])
                                    ->dehydrateStateUsing(fn(?string $state) => $state ? Carbon::parse($state)->hour : null)
                                    ->afterStateHydrated(fn($component, $state) => $component->state($state !== null ? sprintf('%02d:00', $state) : null)),
                            ]),
                    ]),

                // === KOLOM KANAN (Sidebar) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Gambar
                        Section::make('Media')
                            ->icon('heroicon-m-photo')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Foto Jasa')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory(fn() => 'jasas/' . now()->format('Y/m/d'))
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                        $datePrefix = now()->format('ymd');
                                        $slug = Str::slug($get('nama_jasa') ?? 'jasa');
                                        $extension = $file->getClientOriginalExtension();
                                        return "{$datePrefix}-{$slug}.{$extension}";
                                    })
                                    ->preserveFilenames(),
                            ]),

                        // Section 4: Identitas Teknis
                        Section::make('Identitas')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('Kode SKU')
                                    ->default(fn() => Jasa::generateSku())
                                    ->disabled() // Tetap disabled
                                    ->dehydrated() // Agar tetap tersimpan ke DB
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Kode unik digenerate otomatis sistem.'),
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
                        InfolistSection::make('Detail Layanan')
                            ->icon('heroicon-m-wrench-screwdriver')
                            ->schema([
                                TextEntry::make('nama_jasa')
                                    ->label('Nama Jasa')
                                    ->weight('bold')
                                    ->size(TextEntrySize::Large)
                                    ->columnSpanFull(),

                                TextEntry::make('deskripsi')
                                    ->label('Deskripsi Lengkap')
                                    ->html() // Merender HTML dari RichEditor
                                    ->prose() // Memberikan styling tipografi yang rapi
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Biaya & Waktu (Highlight Info)
                        InfolistSection::make('Penawaran')
                            ->icon('heroicon-m-currency-dollar')
                            ->schema([
                                InfolistGrid::make(2) // Grid 2 kolom agar seimbang
                                    ->schema([
                                        TextEntry::make('harga')
                                            ->label('Biaya Jasa')
                                            ->money('IDR') // Format Rupiah otomatis
                                            ->color('success') // Warna hijau agar menarik
                                            ->weight('bold')
                                            ->size(TextEntrySize::Large),

                                        TextEntry::make('estimasi_waktu_jam')
                                            ->label('Estimasi Pengerjaan')
                                            ->icon('heroicon-m-clock')
                                            ->suffix(' Jam') // Memberikan konteks satuan
                                            ->placeholder('Tidak ada estimasi'),
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
                                    ->label('') // Label dikosongkan agar gambar fokus
                                    ->disk('public')
                                    ->height(200)
                                    ->extraImgAttributes([
                                        'class' => 'object-cover rounded-lg shadow-sm w-full',
                                        'alt' => 'Foto Jasa',
                                    ]),
                            ]),

                        // Section 4: Data Teknis
                        InfolistSection::make('Identitas')
                            ->icon('heroicon-m-finger-print')
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('Kode SKU')
                                    ->copyable() // Memudahkan admin copy kode
                                    ->fontFamily(FontFamily::Mono),

                                IconEntry::make('is_active')
                                    ->label('Status Aktif')
                                    ->boolean(), // Menampilkan ceklis/silang

                                TextEntry::make('created_at')
                                    ->label('Terdaftar Sejak')
                                    ->dateTime('d M Y')
                                    ->size(TextEntrySize::Small)
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
                TextColumn::make('nama_jasa')
                    ->label('Nama Jasa')
                    ->color('primary')
                    ->weight('bold')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->description(fn(Jasa $record) => $record->sku)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('harga_formatted')
                    ->label('Harga')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('estimasi_waktu_jam')
                    ->label('Estimasi Waktu (Jam)')
                    ->prefix('')
                    ->badge()
                    ->suffix(' Jam')
                    ->icon('heroicon-m-clock')
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListJasas::route('/'),
            'create' => Pages\CreateJasa::route('/create'),
            'view' => Pages\ViewJasa::route('/{record}'),
            'edit' => Pages\EditJasa::route('/{record}/edit'),
        ];
    }
}
