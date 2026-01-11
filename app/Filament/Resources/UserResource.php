<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Karyawan;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\Village;

class UserResource extends BaseResource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'hugeicons-ai-user';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationParentItem = 'User & Supplier';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Karyawan';

    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('view_any_user')
            || $user?->can('view_limit_user')
            || false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('karyawan', 'roles');
        $user = Filament::auth()->user();

        if ($user?->can('view_limit_user') && ! $user->can('view_any_user')) {
            $query->whereKey($user->id);
        }

        return $query;
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
                                Forms\Components\TextInput::make('karyawan.nama_karyawan')
                                    ->dehydrateStateUsing(fn ($state) => Str::title($state))
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->placeholder('Nama sesuai KTP')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        $set('karyawan.slug', Str::slug($state ?? ''));
                                        $set('name', $state); // Sync to User.name
                                    })
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('karyawan.slug')
                                            ->label('Slug')
                                            ->dehydrateStateUsing(fn ($state) => Str::slug($state))
                                            ->required()
                                            ->readOnly()
                                            ->dehydrated(),

                                        Forms\Components\TextInput::make('karyawan.telepon')
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
                                Forms\Components\Textarea::make('karyawan.alamat')
                                    ->label('Alamat Lengkap')
                                    ->dehydrateStateUsing(fn ($state) => Str::title($state))
                                    ->rows(3)
                                    ->placeholder('Jalan, RT/RW, Nomor Rumah...')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Select::make('karyawan.provinsi')
                                            ->label('Provinsi')
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => Province::query()
                                                ->orderBy('name')
                                                ->pluck('name', 'name')
                                                ->all())
                                            ->live()
                                            ->afterStateUpdated(function (callable $set): void {
                                                $set('karyawan.kota', null);
                                                $set('karyawan.kecamatan', null);
                                                $set('karyawan.kelurahan', null);
                                            })
                                            ->placeholder('Pilih provinsi'),
                                        Select::make('karyawan.kota')
                                            ->label('Kota/Kabupaten')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $provinceName = $get('karyawan.provinsi');
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
                                                $set('karyawan.kecamatan', null);
                                                $set('karyawan.kelurahan', null);
                                            })
                                            ->placeholder('Pilih kota/kabupaten'),
                                        Select::make('karyawan.kecamatan')
                                            ->label('Kecamatan')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $cityName = $get('karyawan.kota');
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
                                                $set('karyawan.kelurahan', null);
                                            })
                                            ->placeholder('Pilih kecamatan'),
                                        Select::make('karyawan.kelurahan')
                                            ->label('Kelurahan/Desa')
                                            ->searchable()
                                            ->preload()
                                            ->options(function (Get $get): array {
                                                $districtName = $get('karyawan.kecamatan');
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

                        // Section 3: Dokumen
                        Section::make('Kelengkapan Dokumen')
                            ->description('Upload berkas penting (KTP, Ijazah, CV, dll).')
                            ->icon('heroicon-m-paper-clip')
                            ->schema([
                                Forms\Components\Repeater::make('karyawan.dokumen_karyawan')
                                    ->label('Daftar Berkas')
                                    ->addActionLabel('Tambah Dokumen')
                                    ->reorderableWithButtons()
                                    ->collapsible()
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
                                                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                                                    ->maxSize(5120)
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
                                Forms\Components\FileUpload::make('karyawan.image_url')
                                    ->label('Foto Wajah')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('karyawan/foto')
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file, Get $get) => (now()->format('ymd').'-'.Str::slug($get('karyawan.nama_karyawan') ?? 'karyawan').'.'.$file->getClientOriginalExtension())
                                    )
                                    ->saveUploadedFileUsing(fn (BaseFileUpload $component, TemporaryUploadedFile $file): ?string => WebpUpload::store($component, $file))
                                    ->columnSpanFull()
                                    ->alignCenter(),
                            ]),

                        // Section 5: Akun Login
                        Section::make('Akses Sistem')
                            ->icon('heroicon-m-lock-closed')
                            ->description('Pengaturan akun login.')
                            ->schema([
                                // 1. Toggle Status Akun
                                Forms\Components\Toggle::make('karyawan.is_active')
                                    ->label('Status Akun Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),

                                // 2. Role Selection
                                Forms\Components\Select::make('roles')
                                    ->label('Role / Jabatan')
                                    ->placeholder('Pilih Role')
                                    ->relationship('roles', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                // 3. Gudang Assignment
                                Forms\Components\Select::make('karyawan.gudang_id')
                                    ->label('Lokasi Gudang')
                                    ->relationship('karyawan.gudang', 'nama_gudang', fn ($query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Pilih gudang untuk validasi absensi berdasarkan koordinat.')
                                    ->placeholder('Pilih gudang')
                                    ->native(false),

                                // 4. Toggle Ubah Email (Hanya muncul saat Mode Edit)
                                Forms\Components\Toggle::make('ubah_email_login')
                                    ->label('Ubah Email?')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->helperText('Aktifkan ini jika ingin mengubah email user.')
                                    ->live()
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation) => $operation === 'edit')
                                    ->default(false),

                                // 4. Toggle Ubah Password (Hanya muncul saat Mode Edit)
                                Forms\Components\Toggle::make('ubah_password_login')
                                    ->label('Ubah Password?')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->helperText('Aktifkan ini jika ingin mengubah password user.')
                                    ->live()
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation) => $operation === 'edit')
                                    ->default(false),

                                // 5. Email Login
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Login')
                                    ->email()
                                    ->required()
                                    ->autocomplete('off')
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_email_login')
                                    )
                                    ->dehydrated(fn (Get $get, string $operation) => $operation === 'create' || $get('ubah_email_login'))
                                    ->unique(User::class, 'email', ignoreRecord: true),

                                // 6. Password
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->autocomplete('new-password')
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_password_login')
                                    )
                                    ->required(fn (string $operation) => $operation === 'create')
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                                // 7. Password Confirmation
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Ulangi Password')
                                    ->password()
                                    ->revealable()
                                    ->same('password')
                                    ->disabled(
                                        fn (Get $get, string $operation) => $operation === 'edit' && ! $get('ubah_password_login')
                                    )
                                    ->required(fn (Get $get) => filled($get('password'))),
                            ]),
                    ]),

                // Hidden field for User.name (synced from karyawan.nama_karyawan)
                Forms\Components\Hidden::make('name'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
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
                                        TextEntry::make('karyawan.nama_karyawan')
                                            ->label('Nama Lengkap')
                                            ->weight('bold')
                                            ->size(TextEntrySize::Large)
                                            ->icon('heroicon-m-identification'),

                                        TextEntry::make('karyawan.telepon')
                                            ->label('Kontak')
                                            ->icon('heroicon-m-device-phone-mobile')
                                            ->copyable()
                                            ->url(fn ($record) => $record->karyawan?->telepon ? 'https://wa.me/'.$record->karyawan->telepon : null, true)
                                            ->color('success'),
                                    ]),

                                TextEntry::make('karyawan.alamat')
                                    ->label('Alamat Domisili')
                                    ->icon('heroicon-m-map-pin')
                                    ->markdown()
                                    ->columnSpanFull(),

                                TextEntry::make('wilayah_lengkap')
                                    ->label('Detail Wilayah')
                                    ->state(
                                        fn ($record) => implode(', ', array_filter([
                                            $record->karyawan?->kelurahan,
                                            $record->karyawan?->kecamatan,
                                            $record->karyawan?->kota,
                                            $record->karyawan?->provinsi,
                                        ]))
                                    )
                                    ->icon('heroicon-m-map')
                                    ->badge()
                                    ->color('gray'),
                            ]),

                        // Section 2: Berkas Dokumen
                        InfolistSection::make('Berkas Dokumen')
                            ->icon('heroicon-m-folder-open')
                            ->schema([
                                RepeatableEntry::make('karyawan.dokumen_karyawan')
                                    ->label('')
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
                                                    ->url(fn ($state) => Storage::url($state))
                                                    ->openUrlInNewTab()
                                                    ->icon('heroicon-m-arrow-down-tray')
                                                    ->color('info')
                                                    ->badge(),
                                            ]),
                                    ])
                                    ->grid(2)
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
                                ImageEntry::make('karyawan.image_url')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->circular()
                                    ->height(200)
                                    ->extraImgAttributes([
                                        'class' => 'mx-auto border-4 border-white shadow-lg',
                                    ]),
                            ]),

                        // Section 4: Akun & Status
                        InfolistSection::make('Status Kepegawaian')
                            ->icon('hugeicons-id')
                            ->schema([
                                IconEntry::make('karyawan.is_active')
                                    ->label('Status Akun')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                TextEntry::make('email')
                                    ->label('Email Login')
                                    ->icon('heroicon-m-at-symbol')
                                    ->copyable(),

                                TextEntry::make('roles.name')
                                    ->label('Jabatan / Role')
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('karyawan.gudang.nama_gudang')
                                    ->label('Lokasi Gudang')
                                    ->icon('heroicon-o-building-storefront')
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('-'),

                                TextEntry::make('karyawan.slug')
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
                ImageColumn::make('karyawan.image_url')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->size(44)
                    ->defaultImageUrl(url('/images/icons/icon-512x512.png')),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-identification')
                    ->description(fn (User $record) => $record->roles->pluck('name')->join(', '))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('karyawan.telepon')
                    ->label('WhatsApp')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->copyable()
                    ->color('success')
                    ->url(fn (User $record) => $record->karyawan?->telepon ? 'https://wa.me/'.$record->karyawan->telepon : null, true)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('roles.name')
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
                IconColumn::make('karyawan.is_active')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
