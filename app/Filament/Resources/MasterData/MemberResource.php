<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\MemberResource\Pages;
use App\Models\Member;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class MemberResource extends BaseResource
{
    protected static ?string $model = Member::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-contact';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'User & Supplier';
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Layout Grid 3 Kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Header Profil
                        InfolistSection::make('Identitas Member')
                            ->icon('heroicon-m-user-circle')
                            ->schema([
                                
                                // Baris 1: Nama Besar
                                TextEntry::make('nama_member')
                                    ->label('Nama Lengkap')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->columnSpanFull(),

                                // Baris 2: Kontak (Grid)
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable() // Fitur copy
                                            ->url(fn ($record) => "mailto:{$record->email}") // Klik untuk kirim email
                                            ->color('info')
                                            ->placeholder('-'),

                                        TextEntry::make('no_hp')
                                            ->label('WhatsApp / Telepon')
                                            ->icon('heroicon-m-device-phone-mobile')
                                            ->url(fn ($record) => "tel:{$record->no_hp}") // Klik untuk telepon
                                            ->color('success'),
                                    ]),
                            ]),

                        // Section 2: Lokasi / Alamat
                        InfolistSection::make('Alamat Domisili')
                            ->icon('heroicon-m-map-pin')
                            ->schema([
                                TextEntry::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->markdown() // Agar teks panjang/multiline rapi
                                    ->columnSpanFull(),

                                // Grid untuk detail wilayah agar rapi sejajar
                                InfolistGrid::make(3) 
                                    ->schema([
                                        TextEntry::make('provinsi')
                                            ->label('Provinsi')
                                            ->icon('heroicon-m-map'),
                                            
                                        TextEntry::make('kota')
                                            ->label('Kota/Kab')
                                            ->icon('heroicon-m-building-office-2'),

                                        TextEntry::make('kecamatan')
                                            ->label('Kecamatan')
                                            ->icon('heroicon-m-building-library'),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR - FOTO) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Visual
                        InfolistSection::make('Foto Profil')
                            ->schema([
                                ImageEntry::make('image_url')
                                    ->label('') // Label di-hidden agar clean
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->circular() // Tampil bulat (avatar style)
                                    ->height(200) // Ukuran konsisten
                                    ->extraImgAttributes([
                                        'class' => 'mx-auto shadow-lg border-4 border-white', // Styling tambahan (Tailwind)
                                        'alt' => 'Foto Member',
                                    ])
                                    ->defaultImageUrl(url('/images/placeholder-avatar.png')), // Opsional: Placeholder
                            ]),

                        // Section 4: Metadata Sistem
                        InfolistSection::make('Info Sistem')
                            ->icon('heroicon-m-cpu-chip')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Bergabung Sejak')
                                    ->dateTime('d F Y')
                                    ->size(TextEntrySize::Small)
                                    ->icon('heroicon-m-calendar')
                                    ->color('gray'),

                                TextEntry::make('updated_at')
                                    ->label('Terakhir Update')
                                    ->dateTime('d M Y, H:i')
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
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Export')
                    ->hidden(true)
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->fileName('member')
                    ->defaultFormat('xlsx')
                    ->withHiddenColumns()
                    ->disableAdditionalColumns()
                    ->filterColumnsFieldLabel('Pilih kolom untuk diexport'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    FilamentExportBulkAction::make('export_selected')
                        ->label('Export (Pilih Baris)')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->fileName('member-terpilih')
                        ->defaultFormat('xlsx')
                        ->withHiddenColumns()
                        ->disableAdditionalColumns()
                        ->filterColumnsFieldLabel('Pilih kolom untuk diexport'),
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
