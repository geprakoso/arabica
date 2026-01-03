<?php

namespace App\Filament\Resources\MasterData;

use Filament\Forms;
// use App\Filament\Resources\MasterData\SupplierResource\RelationManagers;
use Filament\Tables;
use Filament\Forms\Get;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Laravolt\Indonesia\Models\City;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Split;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Dom\Text;
use Filament\Forms\Components\Section;
// use Ramsey\Uuid\Type\Time;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use App\Filament\Resources\MasterData\SupplierResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\MasterData\SupplierResource\RelationManagers\AgentsRelationManager;

class SupplierResource extends BaseResource
{
    protected static ?string $model = Supplier::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-truck';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'User & Supplier';
    protected static ?string $navigationLabel = 'Supplier';
    protected static ?string $pluralLabel = 'Supplier';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Grid utama 3 kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA & ALAMAT) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Profil Supplier
                        Section::make('Profil Perusahaan')
                            ->dehydrateStateUsing(fn($state) => Str::title($state))
                            ->description('Identitas utama supplier.')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([
                                Forms\Components\TextInput::make('nama_supplier')
                                    ->label('Nama Supplier / PT')
                                    ->required()
                                    ->placeholder('Masukan nama perusahaan')
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Alamat (Kita buat lebar agar leluasa)
                        Section::make('Alamat Lengkap')
                            ->icon('heroicon-m-map')
                            ->dehydrateStateUsing(fn($state) => Str::title($state))
                            ->schema([
                                Forms\Components\Textarea::make('alamat')
                                    ->label('Jalan / Gedung')
                                    ->rows(4)
                                    ->placeholder('Masukan alamat lengkap...')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (KONTAK & WILAYAH) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Kontak (Sidebar atas - High Priority)
                        Section::make('Kontak Person')
                            ->icon('heroicon-m-phone')
                            ->schema([
                                Forms\Components\TextInput::make('no_hp')
                                    ->label('No. Handphone / WA')
                                    ->tel()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('08xxxxxxxxxx'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Kantor')
                                    ->email()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('email@perusahaan.com'),
                            ]),

                        // Section 4: Detail Wilayah
                        Section::make('Area Wilayah')
                            ->dehydrateStateUsing(fn($state) => Str::title($state))
                            ->schema([
                                Forms\Components\Select::make('provinsi')
                                    ->label('Provinsi')
                                    ->searchable()
                                    ->options(fn() => Province::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->all())
                                    ->live()
                                    ->afterStateUpdated(function (callable $set): void {
                                        $set('kota', null);
                                        $set('kecamatan', null);
                                    })
                                    ->placeholder('Pilih provinsi'),
                                Forms\Components\Select::make('kota')
                                    ->label('Kota/Kabupaten')
                                    ->searchable()
                                    ->options(function (Get $get): array {
                                        $provinceName = $get('provinsi');
                                        if (!$provinceName) {
                                            return [];
                                        }

                                        $provinceCode = Province::query()
                                            ->where('name', $provinceName)
                                            ->value('code');

                                        if (!$provinceCode) {
                                            return [];
                                        }

                                        return City::query()
                                            ->where('province_code', $provinceCode)
                                            ->orderBy('name')
                                            ->pluck('name', 'name')
                                            ->all();
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('kecamatan', null))
                                    ->placeholder('Pilih kota/kabupaten'),


                                Forms\Components\Select::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->searchable()
                                    ->options(function (Get $get): array {
                                        $cityName = $get('kota');
                                        if (!$cityName) {
                                            return [];
                                        }

                                        $cityCode = City::query()
                                            ->where('name', $cityName)
                                            ->value('code');

                                        if (!$cityCode) {
                                            return [];
                                        }

                                        return District::query()
                                            ->where('city_code', $cityCode)
                                            ->orderBy('name')
                                            ->pluck('name', 'name')
                                            ->all();
                                    })
                                    ->placeholder('Pilih kecamatan'),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Layout grid 3 kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Header Profil
                        InfolistSection::make('Profil Supplier')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([
                                TextEntry::make('nama_supplier')
                                    ->label('Nama Perusahaan')
                                    ->weight(FontWeight::Bold) // Font tebal
                                    ->size(TextEntry\TextEntrySize::Large) // Ukuran besar
                                    ->icon('heroicon-m-check-badge') // Icon verifikasi visual
                                    ->color('primary')
                                    ->columnSpanFull(),

                                TextEntry::make('alamat')
                                    ->label('Alamat Lengkap')
                                    ->icon('heroicon-m-map-pin')
                                    ->markdown() // Agar teks panjang terlihat rapi seperti paragraf
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Data Sistem (Opsional, biar kolom kiri tidak terlalu kosong)
                        InfolistSection::make('Informasi Sistem')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Terdaftar Sejak')
                                            ->dateTime('d F Y')
                                            ->icon('heroicon-m-calendar'),

                                        TextEntry::make('updated_at')
                                            ->label('Terakhir Update')
                                            ->dateTime('d F Y H:i')
                                            ->icon('heroicon-m-clock')
                                            ->color('gray'),
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Kontak (Actionable)
                        InfolistSection::make('Hubungi Kami')
                            ->icon('heroicon-m-phone')
                            ->schema([
                                TextEntry::make('no_hp')
                                    ->label('WhatsApp / Telepon')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->copyable() // Fitur copy nomor
                                    ->copyMessage('Nomor HP disalin')
                                    ->url(fn($record) => "tel:{$record->no_hp}") // Klik untuk menelepon
                                    ->color('success'),

                                TextEntry::make('email')
                                    ->label('Email Kantor')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable()
                                    ->url(fn($record) => "mailto:{$record->email}") // Klik untuk email
                                    ->color('info'),
                            ]),

                        // Section 4: Wilayah
                        InfolistSection::make('Area Operasional')
                            ->icon('heroicon-m-globe-asia-australia')
                            ->schema([
                                TextEntry::make('provinsi')
                                    ->label('Provinsi')
                                    ->badge() // Menggunakan badge agar terlihat seperti tag
                                    ->color('gray'),

                                TextEntry::make('kota')
                                    ->label('Kota / Kab')
                                    ->icon('heroicon-m-building-office'),

                                TextEntry::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->icon('heroicon-m-map'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_supplier')
                    ->label('Nama Supplier')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->sortable()
                    ->searchable(),
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
            AgentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
