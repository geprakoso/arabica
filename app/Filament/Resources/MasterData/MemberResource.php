<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\MemberResource\Pages;
use App\Models\Member;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-contact';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'User';
    protected static ?string $pluralLabel = 'Member';
    protected static ?string $navigationLabel = 'Member';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Grid utama 3 kolom
            ->schema([
                
                // === KOLOM KIRI (DATA UTAMA) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        
                        // Section 1: Data Diri
                        Section::make('Informasi Personal')
                            ->description('Data diri lengkap member.')
                            ->icon('heroicon-m-user')
                            ->schema([
                                TextInput::make('nama_member')
                                    ->label('Nama Lengkap')
                                    ->required()
                                    ->placeholder('Masukan nama lengkap')
                                    ->columnSpanFull(), // Agar nama terlihat dominan

                                Grid::make(2) // Baris kontak
                                    ->schema([
                                        TextInput::make('no_hp')
                                            ->label('Nomor WhatsApp / HP')
                                            ->tel()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('08xxxxxxxxxx'),
                                        
                                        TextInput::make('email')
                                            ->label('Alamat Email')
                                            ->email()
                                            ->placeholder('nama@email.com')
                                            ->nullable(),
                                    ]),
                            ]),

                        // Section 2: Alamat (Dulu di Tab, sekarang di Section bawahnya)
                        Section::make('Alamat Domisili')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                Textarea::make('alamat') // Ganti textinput jadi textarea agar muat banyak
                                    ->label('Alamat Lengkap')
                                    ->rows(3)
                                    ->placeholder('Nama jalan, nomor rumah, RT/RW...')
                                    ->columnSpanFull(),

                                Grid::make(3) // Grid 3 untuk wilayah
                                    ->schema([
                                        TextInput::make('provinsi')
                                            ->label('Provinsi'),
                                        TextInput::make('kota')
                                            ->label('Kota/Kabupaten'),
                                        TextInput::make('kecamatan')
                                            ->label('Kecamatan'),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        
                        // Section 3: Foto Profil
                        Section::make('Foto Profil')
                            ->icon('heroicon-m-camera')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Foto Wajah')
                                    ->image()
                                    ->avatar() // Mode avatar (bulat/crop circle)
                                    ->imageEditor()
                                    ->circleCropper() // Agar cropnya bulat (opsional, bagus untuk profil)
                                    ->disk('public')
                                    ->directory('members/' . now()->format('Y/m/d')) // Fix: Folder members
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                        $datePrefix = now()->format('ymd');
                                        $slug = Str::slug($get('nama_member') ?? 'member'); // Fix: Slug dari nama_member
                                        $extension = $file->getClientOriginalExtension();
                                        return "{$datePrefix}-{$slug}.{$extension}";
                                    })
                                    ->preserveFilenames(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_member')
                    ->label('Nama Member')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}
