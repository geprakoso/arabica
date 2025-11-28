<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjadwalanServiceResource\Pages;
use App\Filament\Resources\PenjadwalanServiceResource\RelationManagers;
use App\Models\PenjadwalanService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Split;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MasterData\JasaResource;

class PenjadwalanServiceResource extends Resource
{
    protected static ?string $model = PenjadwalanService::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Penjadwalan';
    protected static ?string $navigationLabel = 'Service';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                FormsGrid::make(3)
                ->schema([
                    // --- KOLOM KIRI (DATA UTAMA) ---
                    FormsGroup::make()
                        ->schema([
                            // Section 1: Data Pelanggan & Unit
                            FormsSection::make('Informasi Pelanggan & Unit')
                                ->description('Data pemilik dan barang yang akan diservice.')
                                ->icon('hugeicons-user-circle') // Icon user
                                ->schema([
                                    Select::make('member_id')
                                        ->label('Pelanggan')
                                        ->relationship('member', 'nama_member') // Relasi ke model Member
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            // Quick Create Member kalau pelanggan baru
                                            TextInput::make('nama_member')->required(),
                                            TextInput::make('no_hp')->required(),
                                        ])
                                        ->required(),

                                    TextInput::make('nama_perangkat')
                                        ->label('Nama Perangkat / Barang')
                                        ->placeholder('Contoh: Laptop Ideapad 5 / VGA Colorful RTX 3060')
                                        ->required(),
                                        
                                    TextInput::make('kelengkapan')
                                        ->label('Kelengkapan')
                                        ->placeholder('Contoh: Unit only, Dus, Charger')
                                        ->columnSpanFull(),
                                ])->columns(2),

                            // Section 2: Diagnosa Awal
                            FormsSection::make('Keluhan & Diagnosa')
                                ->icon('hugeicons-clipboard') // Icon papan jalan
                                ->schema([
                                    Textarea::make('keluhan')
                                        ->label('Keluhan Pelanggan')
                                        ->rows(3)
                                        ->required(),

                                    Textarea::make('catatan_teknisi')
                                        ->label('Catatan Awal / Kondisi Fisik')
                                        ->placeholder('Contoh: Lecet di bagian bezel, layar retak halus.')
                                        ->rows(3),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // --- KOLOM KANAN (ADMINISTRASI) ---
                    FormsGroup::make()
                        ->schema([
                            FormsSection::make('Status & Penugasan')
                                ->icon('hugeicons-settings-01')
                                ->schema([
                                    // Auto Generate No Resi
                                    TextInput::make('no_resi')
                                        ->label('No. Resi Service')
                                        ->default(fn () => 'SRV-' . now()->format('ymd') . '-' . rand(100, 999))
                                        ->disabled()
                                        ->dehydrated()
                                        ->required(),

                                    Select::make('status')
                                        ->options([
                                            'pending' => 'Menunggu Antrian',
                                            'diagnosa' => 'Sedang Diagnosa',
                                            'waiting_part' => 'Menunggu Sparepart',
                                            'progress' => 'Sedang Dikerjakan',
                                            'done' => 'Selesai (Siap Ambil)',
                                            'cancel' => 'Dibatalkan',
                                        ])
                                        ->default('pending')
                                        ->native(false)
                                        ->required(),

                                    Select::make('technician_id')
                                        ->label('Teknisi')
                                        ->relationship('technician', 'name') // Relasi ke User
                                        ->searchable()
                                        ->preload(),

                                    Select::make('jasa_id')
                                        ->label('Layanan Utama')
                                        ->relationship('jasa', 'nama_jasa') // Relasi ke Model Jasa
                                        ->searchable()
                                        ->preload(),
                                        
                                    DatePicker::make('estimasi_selesai')
                                        ->label('Estimasi Selesai')
                                        ->native(false),
                                ]),
                                
                            // Info Tambahan (Read Only)
                            FormsSection::make()
                                ->schema([
                                    Placeholder::make('created_at')
                                        ->label('Diterima Tanggal')
                                        ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_resi')
                    ->label('No. Resi')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->limit(30)
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Antrian',
                        'diagnosa' => 'Sedang Diagnosa',
                        'waiting_part' => 'Menunggu Sparepart',
                        'progress' => 'Sedang Dikerjakan',
                        'done' => 'Selesai',
                        'cancel' => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'diagnosa' => 'info',
                        'waiting_part' => 'warning',
                        'progress' => 'info',
                        'done' => 'success',
                        'cancel' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->placeholder('-')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('estimasi_selesai')
                    ->label('Estimasi Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Antrian',
                        'diagnosa' => 'Sedang Diagnosa',
                        'waiting_part' => 'Menunggu Sparepart',
                        'progress' => 'Sedang Dikerjakan',
                        'done' => 'Selesai',
                        'cancel' => 'Dibatalkan',
                    ]),
                SelectFilter::make('technician_id')
                    ->label('Teknisi')
                    ->relationship('technician', 'name'),
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

    public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([
            InfolistGrid::make(3)
                ->schema([
                    // --- KOLOM KIRI (DETAIL UTAMA) ---
                    InfolistGroup::make()
                        ->schema([
                            // Section Header: Informasi Perangkat & Pemilik
                            InfolistSection::make('Detail Service')
                                ->icon('heroicon-m-device-phone-mobile')
                                ->schema([
                                    // Baris 1: Perangkat (Highlight Besar)
                                    TextEntry::make('nama_perangkat')
                                        ->label('Unit / Perangkat')
                                        ->weight(FontWeight::Bold)
                                        ->size(TextEntrySize::Large)
                                        ->columnSpanFull(),
                                    
                                    // Baris 2: Kelengkapan
                                    TextEntry::make('kelengkapan')
                                        ->label('Kelengkapan Unit')
                                        ->icon('heroicon-m-archive-box')
                                        ->color('gray')
                                        ->columnSpanFull(),

                                    // Baris 3: Data Pemilik (Grid mini 2 kolom)
                                    InfolistGroup::make()
                                        ->schema([
                                            TextEntry::make('member.nama_member')
                                                ->label('Pemilik')
                                                ->icon('heroicon-m-user-circle')
                                                ->weight(FontWeight::Medium),
                                            
                                            TextEntry::make('member.no_hp')
                                                ->label('Nomor HP')
                                                ->icon('heroicon-m-phone')
                                                ->copyable() // Agar mudah dicopy admin
                                                ->url(fn ($record) => 'https://wa.me/' . $record->member->no_hp, true) // Klik langsung ke WA
                                                ->color('primary'),
                                        ])
                                        ->columns(2)
                                        ->columnSpanFull(),
                                ]),

                            // Section: Diagnosa (Layout Full Text)
                            InfolistSection::make('Catatan & Diagnosa')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->schema([
                                    TextEntry::make('keluhan')
                                        ->label('Keluhan Pelanggan')
                                        ->markdown()
                                        ->prose()
                                        ->color('gray'),

                                    TextEntry::make('catatan_teknisi')
                                        ->label('Catatan Teknisi')
                                        ->markdown()
                                        ->placeholder('Belum ada catatan teknisi')
                                        ->color('gray')
                                        ->extraAttributes(['class' => 'italic']),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // --- KOLOM KANAN (SIDEBAR INFO) ---
                    InfolistGroup::make()
                        ->schema([
                            // Kartu Status (Paling Atas & Menonjol)
                            InfolistSection::make('Status Order')
                                ->compact()
                                ->schema([
                                    TextEntry::make('no_resi')
                                        ->label('No. Resi')
                                        ->weight(FontWeight::Bold)
                                        ->fontFamily(FontFamily::Mono) // Font seperti tiket
                                        ->copyable()
                                        ->icon('heroicon-m-qr-code'),

                                    TextEntry::make('status')
                                        ->badge()
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'pending' => 'Menunggu Antrian',
                                            'diagnosa' => 'Sedang Diagnosa',
                                            'waiting_part' => 'Menunggu Sparepart',
                                            'progress' => 'Sedang Dikerjakan',
                                            'done' => 'Selesai',
                                            'cancel' => 'Dibatalkan',
                                            default => $state,
                                        })
                                        ->color(fn (string $state): string => match ($state) {
                                            'pending' => 'gray',
                                            'diagnosa' => 'info',
                                            'waiting_part' => 'warning', // Kuning mencolok
                                            'progress' => 'info',
                                            'done' => 'success', // Hijau
                                            'cancel' => 'danger', // Merah
                                            default => 'gray',
                                        }),
                                        
                                    TextEntry::make('created_at')
                                        ->label('Masuk Tanggal')
                                        ->date('d M Y, H:i')
                                        ->size(TextEntrySize::Small)
                                        ->color('gray'),
                                ]),

                            // Kartu Pengerjaan
                            InfolistSection::make('Pengerjaan')
                                ->compact()
                                ->icon('heroicon-m-wrench-screwdriver')
                                ->schema([
                                    TextEntry::make('technician.name')
                                        ->label('Teknisi')
                                        ->placeholder('Belum ditunjuk'),
                                        
                                    TextEntry::make('jasa.nama_jasa')
                                        ->label('Layanan Jasa')
                                        ->color('primary')
                                        ->url(fn ($record) => $record->jasa ? JasaResource::getUrl('view', ['record' => $record->jasa]) : null),

                                    TextEntry::make('estimasi_selesai')
                                        ->label('Estimasi Selesai')
                                        ->date('d M Y')
                                        ->badge()
                                        ->color('gray')
                                        ->icon('heroicon-m-calendar'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
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
            'index' => Pages\ListPenjadwalanServices::route('/'),
            'create' => Pages\CreatePenjadwalanService::route('/create'),
            'view' => Pages\ViewPenjadwalanService::route('/{record}'),
            'edit' => Pages\EditPenjadwalanService::route('/{record}/edit'),
        ];
    }
}
