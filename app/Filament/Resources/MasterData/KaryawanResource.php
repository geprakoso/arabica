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

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-ai-user';
    // protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'Master Data';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?int $navigationSort = 11;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Split::make([
                    Tabs::make('Detail Karyawan')
                        ->tabs([
                            Tabs\Tab::make('Data Karyawan')
                                ->schema([
                                    TextInput::make('nama_karyawan')
                                        ->label('Nama Karyawan')
                                        ->required(),
                                    TextInput::make('telepon')
                                        ->label('No. HP')
                                        ->required(),
                                ]),
                            Tabs\Tab::make('Alamat')
                                ->schema([
                                    TextInput::make('alamat')
                                        ->label('Alamat')
                                        ->nullable(),
                                    TextInput::make('provinsi')
                                        ->label('Provinsi')
                                        ->nullable(),
                                    TextInput::make('kota')
                                        ->label('Kota')
                                        ->nullable(),
                                    TextInput::make('kecamatan')
                                        ->label('Kecamatan')
                                        ->nullable(),
                                    TextInput::make('kelurahan')
                                        ->label('Kelurahan')
                                        ->nullable(),
                                ]),
                        ]),
                        Section::make('Profile Picture')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Gambar Karyawan')
                                    ->image()
                                    ->disk('public')
                                    ->directory(fn () => 'karyawan/' . now()->format('Y/m/d'))
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                        $datePrefix = now()->format('ymd');
                                        $slug = Str::slug($get('nama_karyawan') ?? 'karyawan');
                                        $extension = $file->getClientOriginalExtension();
                                        return "{$datePrefix}-{$slug}.{$extension}";
                                    })
                                    ->preserveFilenames(),
                                ]),
                    ])->from('lg')
                        ->columnSpanFull(),
                    
                    Section::make('Status')
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->required(),

                        ]),
                    Section::make('Akun login')
                        ->schema([
                            TextInput::make('login_email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->unique(User::class, 'email', ignoreRecord: true),
                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->required(fn ($livewire) => $livewire instanceof Pages\CreateKaryawan)
                                ->dehydrated(fn ($state) => filled($state))
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                            TextInput::make('password_confirmation')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->revealable()
                                ->same('password')
                                ->required(fn ($livewire) => $livewire instanceof Pages\CreateKaryawan),
                            Select::make('role_id')
                                ->label('Role')
                                ->relationship('role', 'name')
                                ->required(),
                        ])->columns(2),
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
