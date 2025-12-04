<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\KaryawanResource\Pages;
use App\Models\Karyawan;
use App\Models\User;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str; // Import Str
use Illuminate\Support\Facades\Hash; // Import Hash
use Filament\Forms\Components\Split;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;
    protected static ?string $recordRouteKeyName = 'slug';

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-ai-user';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'User';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?int $navigationSort = 11;


    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Identitas
                        Section::make('Informasi Personal')
                            ->description('Data diri dan kontak karyawan.')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Forms\Components\TextInput::make('nama_karyawan')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->placeholder('Nama sesuai KTP')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->unique(Karyawan::class, 'slug', ignoreRecord: true)
                                            ->readOnly()
                                            ->dehydrated(),
                                        
                                        Forms\Components\TextInput::make('telepon')
                                            ->label('No. Handphone / WA')
                                            ->tel()
                                            ->required()
                                            ->placeholder('08xxxxxxxxxx'),
                                    ]),
                            ]),

                        // Section 2: Alamat
                        Section::make('Alamat Domisili')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                Forms\Components\Textarea::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->rows(3)
                                    ->placeholder('Jalan, RT/RW, Nomor Rumah...')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('provinsi')->label('Provinsi'),
                                        Forms\Components\TextInput::make('kota')->label('Kota/Kabupaten'),
                                        Forms\Components\TextInput::make('kecamatan')->label('Kecamatan'),
                                        Forms\Components\TextInput::make('kelurahan')->label('Kelurahan/Desa'),
                                    ]),
                            ]),

                        // Section 3: Dokumen (YANG DIPERBAIKI)
                        Section::make('Kelengkapan Dokumen')
                            ->description('Upload berkas penting (KTP, Ijazah, CV, dll).')
                            ->icon('heroicon-m-paper-clip') // Icon paperclip lebih relevan
                            ->schema([
                                // Menggunakan Repeater agar lebih rapi: Ada Nama Dokumen & Filenya
                                Forms\Components\Repeater::make('dokumen_karyawan')
                                    ->label('Daftar Berkas')
                                    ->addActionLabel('Tambah Dokumen')
                                    ->reorderableWithButtons()
                                    ->collapsible() // Bisa dilipat agar tidak memakan tempat
                                    ->itemLabel(fn (array $state): ?string => $state['jenis_dokumen'] ?? 'Dokumen Baru')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('jenis_dokumen')
                                                    ->label('Jenis Dokumen')
                                                    ->placeholder('Contoh: KTP / Ijazah S1')
                                                    ->required()
                                                    ->columnSpan(1),

                                                Forms\Components\FileUpload::make('file_path')
                                                    ->label('Upload File')
                                                    ->disk('public')
                                                    ->directory('karyawan/dokumen')
                                                    ->acceptedFileTypes(['application/pdf', 'image/*']) // PDF & Gambar
                                                    ->maxSize(5120) // Maks 5MB
                                                    ->openable()
                                                    ->downloadable()
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 4: Foto
                        Section::make('Foto Profil')
                            ->icon('heroicon-m-camera')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Foto Wajah')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->disk('public')
                                    ->directory('karyawan/foto')
                                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file, Get $get) => 
                                        (now()->format('ymd') . '-' . Str::slug($get('nama_karyawan') ?? 'karyawan') . '.' . $file->getClientOriginalExtension())
                                    )
                                    ->preserveFilenames()
                                    ->columnSpanFull()
                                    ->alignCenter(), // Agar posisi di tengah
                            ]),

                        // Section 5: Akun Login
                        Section::make('Akses Sistem')
                            ->icon('heroicon-m-lock-closed') // Icon gembok
                            ->description('Pengaturan akun login.')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Akun Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),

                                Forms\Components\TextInput::make('login_email')
                                    ->label('Email Login')
                                    ->email()
                                    ->required()
                                    ->rules(function (Get $get, ?Karyawan $record) {
                                        return [
                                            Rule::unique('users', 'email')->ignore($record?->user_id),
                                        ];
                                    }),

                                Forms\Components\Select::make('role_id')
                                    ->label('Role / Jabatan')
                                    ->relationship('role', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateKaryawan),

                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Ulangi Password')
                                    ->password()
                                    ->revealable()
                                    ->same('password')
                                    ->required(fn ($livewire) => $livewire instanceof Pages\CreateKaryawan),
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

                        // Section 1: Data Diri
                        InfolistSection::make('Profil Karyawan')
                            ->icon('heroicon-m-user')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('nama_karyawan')
                                            ->label('Nama Lengkap')
                                            ->weight('bold')
                                            ->size(TextEntrySize::Large)
                                            ->icon('heroicon-m-identification'),

                                        TextEntry::make('telepon')
                                            ->label('Kontak')
                                            ->icon('heroicon-m-device-phone-mobile')
                                            ->url(fn ($record) => "tel:{$record->telepon}")
                                            ->color('primary'),
                                    ]),

                                TextEntry::make('alamat')
                                    ->label('Alamat Domisili')
                                    ->icon('heroicon-m-map-pin')
                                    ->markdown() // Agar multiline terbaca rapi
                                    ->columnSpanFull(),

                                // Menampilkan detail wilayah dalam satu baris (inline)
                                TextEntry::make('wilayah_lengkap')
                                    ->label('Detail Wilayah')
                                    ->state(fn ($record) => 
                                        implode(', ', array_filter([
                                            $record->kelurahan, 
                                            $record->kecamatan, 
                                            $record->kota, 
                                            $record->provinsi
                                        ]))
                                    )
                                    ->icon('heroicon-m-map'),
                            ]),

                        // Section 2: Berkas Dokumen (REPEATER DISPLAY)
                        InfolistSection::make('Berkas Dokumen')
                            ->icon('heroicon-m-folder-open')
                            ->schema([
                                RepeatableEntry::make('dokumen_karyawan')
                                    ->label('') // Kosongkan label agar tidak redundan
                                    ->schema([
                                        InfolistGrid::make(2)
                                            ->schema([
                                                TextEntry::make('jenis_dokumen')
                                                    ->label('Jenis Dokumen')
                                                    ->icon('heroicon-m-document-text')
                                                    ->weight('bold'),

                                                TextEntry::make('file_path')
                                                    ->label('File')
                                                    ->formatStateUsing(fn () => 'Unduh / Lihat File')
                                                    ->url(fn ($state) => Storage::url($state)) // Link ke file public
                                                    ->openUrlInNewTab()
                                                    ->icon('heroicon-m-arrow-down-tray')
                                                    ->color('info')
                                                    ->badge(), // Tampil sebagai tombol kecil
                                            ]),
                                    ])
                                    ->grid(2) // Menampilkan 2 dokumen per baris (opsional, jika banyak)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Foto
                        InfolistSection::make('Foto Profil')
                            ->schema([
                                ImageEntry::make('image_url')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->circular() // Tampil bulat sempurna
                                    ->height(200)
                                    ->extraImgAttributes([
                                        'class' => 'mx-auto border-4 border-white shadow-lg', // Styling tambahan
                                    ]),
                            ]),

                        // Section 4: Akun & Status
                        InfolistSection::make('Status Kepegawaian')
                            ->icon('hugeicons-id')
                            ->schema([
                                IconEntry::make('is_active')
                                    ->label('Status Akun')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                TextEntry::make('user.email')
                                    ->label('Email Login')
                                    ->icon('heroicon-m-at-symbol')
                                    ->copyable(),

                                TextEntry::make('role.name')
                                    ->label('Jabatan / Role')
                                    ->badge()
                                    ->color('warning'), // Warna jabatan
                                    
                                TextEntry::make('slug')
                                    ->label('ID Slug')
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
                TextColumn::make('nama_karyawan')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('telepon')
                    ->label('No. HP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('role.name')
                    ->label('Role')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'view' => Pages\ViewKaryawan::route('/{record}'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}
