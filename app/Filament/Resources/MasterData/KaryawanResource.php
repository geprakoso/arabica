<?php

namespace App\Filament\Resources\MasterData;

use Dom\Text;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Karyawan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str; // Import Str
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Hash; // Import Hash
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use App\Filament\Forms\Components\MediaManagerPicker;
use App\Filament\Resources\MasterData\KaryawanResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Validation\Rule;

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
                                MediaManagerPicker::make('dokumen_karyawan')
                                    ->label('Upload Dokumen (Gallery)')
                                    ->disk('public')
                                    ->maxItems(10)
                                    ->reorderable()
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
                                    ->helperText('Aktifkan ini jika ingin mengubah password user.')
                                    ->live()
                                    ->dehydrated(false)
                                    ->visible(fn (string $operation) => $operation === 'edit')
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
                                    ->disabled(fn (Get $get, string $operation) => 
                                        $operation === 'edit' && ! $get('ubah_email_login')
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
                                    ->disabled(fn (Get $get, string $operation) => 
                                        $operation === 'edit' && ! $get('ubah_password_login')
                                    )
                                    // Hanya required saat Create (Saat edit boleh kosong jika tidak ingin ubah password)
                                    ->required(fn (string $operation) => $operation === 'create')
                                    // Simpan hanya jika ada isinya
                                    ->dehydrated(fn ($state, Get $get, string $operation) => filled($state) && ($operation === 'create' || ($get('ubah_password_login') ?? false)))
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),

                                // 6. Password Confirmation
                                Forms\Components\TextInput::make('password_confirmation')
                                    ->label('Ulangi Password')
                                    ->password()
                                    ->revealable()
                                    ->same('password')
                                    // Logic Disable sama
                                    ->disabled(fn (Get $get, string $operation) => 
                                        $operation === 'edit' && ! $get('ubah_password_login')
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
                                ViewEntry::make('dokumen_karyawan_gallery')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.components.media-manager-gallery')
                                    ->state(fn (\App\Models\Karyawan $record) => $record->dokumenKaryawanGallery()),
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
