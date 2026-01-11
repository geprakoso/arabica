<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\MasterData\KaryawanResource\Pages;
use App\Models\Karyawan;
use App\Support\WebpUpload;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry; // Import Str
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry; // Import Hash
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class KaryawanResource extends BaseResource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $recordRouteKeyName = 'slug';

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-ai-user';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationParentItem = 'User & Supplier';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Karyawan';

    protected static ?int $navigationSort = 11;

    /**
     * Hide this resource from navigation.
     * Karyawan data is now managed via UserResource.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
                                    ->dehydrateStateUsing(fn ($state) => Str::title($state))
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
                                            ->dehydrateStateUsing(fn ($state) => Str::slug($state))
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
                                    ->dehydrateStateUsing(fn ($state) => Str::title($state))
                                    ->rows(3)
                                    ->placeholder('Jalan, RT/RW, Nomor Rumah...')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Select::make('provinsi')
                                            ->label('Provinsi')
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => Province::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'name')
                                                ->all())
                                            ->live()
                                            ->afterStateUpdated(function (callable $set): void {
                                                $set('kota', null);
                                                $set('kecamatan', null);
                                                $set('kelurahan', null);
                                            })
                                            ->placeholder('Pilih provinsi'),
                                        Select::make('kota')
                                            ->label('Kota/Kabupaten')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $provinceName = $get('provinsi');
                                                if (! $provinceName) {
                                                    return [];
                                                }

                                                $provinceCode = Province::query()
                                                    ->where('name', $provinceName)
                                                    ->value('code');

                                                if (! $provinceCode) {
                                                    return [];
                                                }

                                                return City::query()
                                                    ->where('province_code', $provinceCode)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'name')
                                                    ->all();
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (callable $set): void {
                                                $set('kecamatan', null);
                                                $set('kelurahan', null);
                                            })
                                            ->placeholder('Pilih kota/kabupaten'),
                                        Select::make('kecamatan')
                                            ->label('Kecamatan')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $cityName = $get('kota');
                                                if (! $cityName) {
                                                    return [];
                                                }

                                                $cityCode = City::query()
                                                    ->where('name', $cityName)
                                                    ->value('code');

                                                if (! $cityCode) {
                                                    return [];
                                                }

                                                return District::query()
                                                    ->where('city_code', $cityCode)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'name')
                                                    ->all();
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (callable $set): void {
                                                $set('kelurahan', null);
                                            })
                                            ->placeholder('Pilih kecamatan'),
                                        Select::make('kelurahan')
                                            ->label('Kelurahan/Desa')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $districtName = $get('kecamatan');
                                                if (! $districtName) {
                                                    return [];
                                                }

                                                $districtCode = District::query()
                                                    ->where('name', $districtName)
                                                    ->value('code');

                                                if (! $districtCode) {
                                                    return [];
                                                }

                                                return Village::query()
                                                    ->where('district_code', $districtCode)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'name')
                                                    ->all();
                                            })
                                            ->placeholder('Pilih kelurahan/desa'),
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
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file, Get $get) => (now()->format('ymd').'-'.Str::slug($get('nama_karyawan') ?? 'karyawan').'.'.$file->getClientOriginalExtension())
                                    )
                                    ->saveUploadedFileUsing(fn (BaseFileUpload $component, TemporaryUploadedFile $file): ?string => WebpUpload::store($component, $file))
                                    ->preserveFilenames()
                                    ->columnSpanFull()
                                    ->alignCenter(), // Agar posisi di tengah
                            ]),

                        // Section 5: Akun Login
                        Section::make('Akses Sistem')
                            ->icon('heroicon-m-lock-closed')
                            ->description('Pengaturan akun login.')
                            ->schema([
                                // 1. Toggle Status Akun (Tetap sama)
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Akun Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),

                                // 4. Role Selection
                                Forms\Components\Select::make('role_id')
                                    ->label('Role / Jabatan')
                                    ->relationship('role', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(false),
                                // Kita bisa kunci juga Role-nya jika mau
                                // ->disabled(fn (Get $get, string $operation) =>
                                //     $operation === 'edit' && ! $get('ubah_akses_login')
                                // ),

                                // 2. Toggle Pemicu "Ubah Email" (Hanya muncul saat Mode Edit)
                                Forms\Components\Toggle::make('ubah_email_login')
                                    ->label('Ubah Email?')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->helperText('Aktifkan ini jika ingin mengubah email user.')
                                    ->live()
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation) => $operation === 'edit')
                                    ->default(false),

                                // 2b. Toggle Pemicu "Ubah Password" (Hanya muncul saat Mode Edit)
                                Forms\Components\Toggle::make('ubah_password_login')
                                    ->label('Ubah Password?')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->helperText('Aktifkan ini jika ingin mengubah email atau password user.')
                                    ->live() // PENTING: Agar form langsung bereaksi saat diklik
                                    ->dehydrated(false) // PENTING: Field ini tidak akan disimpan ke database
                                    ->visible(fn (string $operation) => $operation === 'edit') // Hanya muncul pas Edit
                                    ->default(false),

                                // 3. Email Login
                                Forms\Components\TextInput::make('login_email')
                                    ->label('Email Login')
                                    ->email()
                                    ->required()
                                    ->autocomplete('off')
                                    ->afterStateHydrated(function (TextInput $component, ?Karyawan $record) {
                                        $component->state($record?->user?->email);
                                    })
                                    // Logic: Disable jika sedang Edit DAN Toggle belum dinyalakan
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_akses_login')
                                    )
                                    ->dehydrated(fn (Get $get, string $operation) => $operation === 'create' || $get('ubah_email_login'))
                                    // Validasi Unique yang Diperbaiki
                                    ->rules(function ($record) {
                                        $userId = $record?->user_id; // Ambil user_id dari relasi karyawan

                                        return [
                                            Rule::unique('users', 'email')->ignore($userId),
                                        ];
                                    }),

                                // 5. Password
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->autocomplete('new-password')
                                    // Logic Disable sama seperti Email
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_akses_login')
                                    )
                                    // Hanya required saat Create (Saat edit boleh kosong jika tidak ingin ubah password)
                                    ->required(fn (string $operation) => $operation === 'create')
                                    // Simpan hanya jika ada isinya
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                                // 6. Password Confirmation
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Ulangi Password')
                                    ->password()
                                    ->revealable()
                                    ->same('password')
                                    // Logic Disable sama
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_akses_login')
                                    )
                                    // Wajib jika password utama diisi
                                    ->required(fn (Get $get) => filled($get('password'))),
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
                                            ->copyable()
                                            ->url(fn ($record) => $record->telepon ? 'https://wa.me/'.$record->telepon : null, true)
                                            ->color('success'),
                                    ]),

                                TextEntry::make('alamat')
                                    ->label('Alamat Domisili')
                                    ->icon('heroicon-m-map-pin')
                                    ->markdown() // Agar multiline terbaca rapi
                                    ->columnSpanFull(),

                                // Menampilkan detail wilayah dalam satu baris (inline)
                                TextEntry::make('wilayah_lengkap')
                                    ->label('Detail Wilayah')
                                    ->state(
                                        fn ($record) => implode(', ', array_filter([
                                            $record->kelurahan,
                                            $record->kecamatan,
                                            $record->kota,
                                            $record->provinsi,
                                        ]))
                                    )
                                    ->icon('heroicon-m-map')
                                    ->badge()
                                    ->color('gray'),
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
                ImageColumn::make('image_url')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->size(44)
                    ->defaultImageUrl(url('/images/icons/icon-512x512.png')),
                TextColumn::make('nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-identification')
                    ->description(fn (Karyawan $record) => $record->role?->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('telepon')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->copyable()
                    ->color('success')
                    ->url(fn (Karyawan $record) => $record->telepon ? 'https://wa.me/'.$record->telepon : null, true)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
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
