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
// use Filament\Resources\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str; // Import Str
use Illuminate\Support\Facades\Hash; // Import Hash
use Filament\Forms\Components\Split;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

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

                // === KOLOM KIRI (DATA PERSONAL) ===
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
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('telepon')
                                    ->label('No. Handphone / WA')
                                    ->tel()
                                    ->required()
                                    ->placeholder('08xxxxxxxxxx'),
                            ]),

                        // Section 2: Alamat
                        Section::make('Alamat Domisili')
                            ->icon('heroicon-m-home')
                            ->schema([
                                Forms\Components\Textarea::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->rows(3)
                                    ->placeholder('Jalan, RT/RW, Nomor Rumah...')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2) // Grid 2 kolom untuk wilayah
                                    ->schema([
                                        Forms\Components\TextInput::make('provinsi')->label('Provinsi'),
                                        Forms\Components\TextInput::make('kota')->label('Kota/Kabupaten'),
                                        Forms\Components\TextInput::make('kecamatan')->label('Kecamatan'),
                                        Forms\Components\TextInput::make('kelurahan')->label('Kelurahan/Desa'),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (AKUN & SISTEM) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Media
                        Section::make('Foto Profil')
                            ->icon('heroicon-m-camera')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Foto Wajah')
                                    ->image()
                                    ->avatar() // Mode bulat
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->disk('public')
                                    ->directory('karyawan/' . now()->format('Y/m/d'))
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                        $datePrefix = now()->format('ymd');
                                        $slug = Str::slug($get('nama_karyawan') ?? 'karyawan');
                                        $extension = $file->getClientOriginalExtension();
                                        return "{$datePrefix}-{$slug}.{$extension}";
                                    })
                                    ->preserveFilenames(),
                            ]),

                        // Section 4: Akses Sistem (Login)
                        Section::make('Akses Sistem')
                            ->icon('heroicon-m-key')
                            ->description('Kredensial login aplikasi.')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false), // Label di atas toggle agar rapi

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
