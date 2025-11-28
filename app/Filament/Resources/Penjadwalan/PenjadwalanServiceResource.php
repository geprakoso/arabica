<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;
use App\Models\PenjadwalanService;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\MasterData\JasaResource;

class PenjadwalanServiceResource extends Resource
{
    protected static ?string $model = PenjadwalanService::class;

    protected static ?string $navigationIcon = 'hugeicons-service';
    protected static ?string $navigationGroup = 'Penjadwalan';
    protected static ?string $navigationLabel = 'Service';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormsGrid::make(3)
                ->schema([
                    // --- KOLOM KIRI (DATA UTAMA) ---
                    FormsGroup::make()
                        ->schema([
                            // Section 1: Data Pelanggan
                            FormsSection::make('Informasi Pelanggan')
                                ->description('Pilih pelanggan atau buat baru.')
                                ->icon('hugeicons-user-circle')
                                ->schema([
                                    Select::make('member_id')
                                        ->label('Pelanggan')
                                        ->relationship('member', 'nama_member')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if (! $state) return;
                                            $member = Member::find($state);
                                            $set('member_no_hp', $member?->no_hp);
                                            $set('member_alamat', $member?->alamat);
                                        })
                                        ->createOptionForm([
                                            TextInput::make('nama_member')->required()->label('Nama Lengkap'),
                                            TextInput::make('no_hp')->required()->label('No WhatsApp'),
                                            TextInput::make('alamat')->label('Alamat Domisili'),
                                        ])
                                        ->required()
                                        ->columnSpanFull(),

                                    // Field Readonly (Tampil Rapi dengan Icon)
                                    FormsGrid::make(2)
                                        ->schema([
                                            TextInput::make('member_no_hp')
                                                ->label('Kontak (Auto)')
                                                ->prefixIcon('heroicon-m-phone')
                                                ->disabled()
                                                ->dehydrated(false),
                                            
                                            TextInput::make('member_alamat')
                                                ->label('Alamat (Auto)')
                                                ->prefixIcon('heroicon-m-map-pin')
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),
                                ]),

                            // Section 2: Unit & Diagnosa
                            FormsSection::make('Unit & Keluhan')
                                ->icon('hugeicons-clipboard')
                                ->schema([
                                    FormsGrid::make(2)
                                        ->schema([
                                            TextInput::make('nama_perangkat')
                                                ->label('Nama Perangkat')
                                                ->placeholder('Contoh: Laptop Lenovo Ideapad 3')
                                                ->required(),
                                            
                                            TextInput::make('kelengkapan')
                                                ->label('Kelengkapan')
                                                ->placeholder('Unit, Charger, Dus...'),
                                        ]),

                                    Textarea::make('keluhan')
                                        ->label('Keluhan Pelanggan')
                                        ->rows(3)
                                        ->required()
                                        ->columnSpanFull(),

                                    Textarea::make('catatan_teknisi')
                                        ->label('Catatan Fisik (Optional)')
                                        ->placeholder('Cth: Lecet bezel, baut hilang satu')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // --- KOLOM KANAN (ADMINISTRASI) ---
                    FormsGroup::make()
                        ->schema([
                            FormsSection::make('Status & Penugasan')
                                ->icon('hugeicons-settings-01')
                                ->schema([
                                    TextInput::make('no_resi')
                                        ->label('No. Resi')
                                        ->default(fn () => 'SRV-' . now()->format('ymd') . '-' . rand(100, 999))
                                        ->readOnly() // Readonly lebih baik visualnya daripada disabled untuk ID
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
                                        ->relationship('technician', 'name')
                                        ->searchable()
                                        ->preload(),

                                    Select::make('jasa_id')
                                        ->label('Layanan Utama')
                                        ->relationship('jasa', 'nama_jasa')
                                        ->searchable()
                                        ->preload(),
                                    
                                    DatePicker::make('estimasi_selesai')
                                        ->label('Estimasi Selesai')
                                        ->native(false),
                                ]),

                            FormsSection::make()
                                ->schema([
                                    Placeholder::make('created_at')
                                        ->label('Waktu Penerimaan')
                                        ->content(fn ($record) => $record?->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i')),
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
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Antrian',
                        'diagnosa' => 'Diagnosa',
                        'waiting_part' => 'Wait Part',
                        'progress' => 'Proses',
                        'done' => 'Selesai',
                        'cancel' => 'Batal',
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
                    ->sortable(),
                TextColumn::make('estimasi_selesai')
                    ->label('Estimasi')
                    ->date('d M')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
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
                    // --- KOLOM KIRI ---
                    InfolistGroup::make()
                        ->schema([
                            InfolistSection::make('Informasi Service')
                                ->icon('heroicon-m-device-phone-mobile')
                                ->schema([
                                    // Header Besar
                                    TextEntry::make('nama_perangkat')
                                        ->label('Unit Service')
                                        ->weight(FontWeight::Bold)
                                        ->size(TextEntrySize::Large)
                                        ->columnSpanFull(),
                                    
                                    TextEntry::make('kelengkapan')
                                        ->icon('heroicon-m-archive-box')
                                        ->color('gray')
                                        ->columnSpanFull(),

                                    // Data Pemilik dalam Grid
                                    InfolistGrid::make(2)
                                        ->schema([
                                            TextEntry::make('member.nama_member')
                                                ->label('Pemilik')
                                                ->icon('heroicon-m-user'),
                                            
                                            TextEntry::make('member.no_hp')
                                                ->label('WhatsApp')
                                                ->icon('heroicon-m-phone')
                                                ->color('primary')
                                                ->url(fn ($record) => 'https://wa.me/' . $record->member->no_hp, true),
                                        ])
                                        ->extraAttributes(['class' => 'mt-4 border-t pt-4']), // Garis pemisah tipis
                                ]),

                            InfolistSection::make('Diagnosa & Keluhan')
                                ->icon('heroicon-m-clipboard-document-list')
                                ->schema([
                                    TextEntry::make('keluhan')
                                        ->label('Keluhan Awal')
                                        ->markdown(),
                                    
                                    TextEntry::make('catatan_teknisi')
                                        ->label('Catatan Teknisi')
                                        ->placeholder('Belum ada catatan')
                                        ->markdown()
                                        ->color('gray')
                                        ->extraAttributes(['class' => 'italic']),
                                ]),
                        ])
                        ->columnSpan(['lg' => 2]),

                    // --- KOLOM KANAN ---
                    InfolistGroup::make()
                        ->schema([
                            InfolistSection::make('Status')
                                ->compact()
                                ->schema([
                                    TextEntry::make('no_resi')
                                        ->fontFamily(FontFamily::Mono)
                                        ->weight(FontWeight::Bold)
                                        ->copyable()
                                        ->icon('heroicon-m-qr-code'),
                                    
                                    TextEntry::make('status')
                                        ->badge()
                                        ->formatStateUsing(fn (string $state): string => match ($state) {
                                            'pending' => 'Antrian',
                                            'diagnosa' => 'Diagnosa',
                                            'waiting_part' => 'Wait Part',
                                            'progress' => 'Proses',
                                            'done' => 'Selesai',
                                            'cancel' => 'Batal',
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
                                        }),
                                        
                                    TextEntry::make('created_at')
                                        ->label('Masuk')
                                        ->date('d M Y, H:i')
                                        ->color('gray'),
                                ]),

                            InfolistSection::make('Pengerjaan')
                                ->compact()
                                ->schema([
                                    TextEntry::make('technician.name')
                                        ->label('Teknisi')
                                        ->icon('heroicon-m-user-circle')
                                        ->placeholder('-'),
                                    
                                    TextEntry::make('jasa.nama_jasa')
                                        ->label('Layanan')
                                        ->color('primary')
                                        ->url(fn ($record) => $record->jasa ? JasaResource::getUrl('view', ['record' => $record->jasa]) : null),

                                    TextEntry::make('estimasi_selesai')
                                        ->label('Deadline')
                                        ->date('d M Y')
                                        ->icon('heroicon-m-calendar'),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ]),
            ]);
    }
    
    // ... relations dan pages tetap sama
    public static function getRelations(): array
    {
        return [];
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