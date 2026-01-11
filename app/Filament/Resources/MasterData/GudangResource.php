<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\MasterData\GudangResource\Pages;
use App\Models\Gudang;
use Dotswan\MapPicker\Fields\Map;
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GudangResource extends BaseResource
{
    protected static ?string $model = Gudang::class;

    protected static ?string $navigationIcon = 'hugeicons-warehouse';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $pluralModelLabel = 'Gudang';

    protected static ?string $navigationLabel = 'Gudang';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Data Gudang
                Section::make('Data Gudang')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        TextInput::make('nama_gudang')
                            ->label('Nama Gudang')
                            ->required()
                            ->placeholder('Contoh: Gudang Utama'),
                        TextInput::make('lokasi_gudang')
                            ->label('Alamat Lokasi')
                            ->required()
                            ->placeholder('Jl. Contoh No. 123'),

                        // Laravolt Indonesia Fields
                        Select::make('provinsi')
                            ->label('Provinsi')
                            ->searchable()
                            ->preload()
                            ->options(fn () => \Laravolt\Indonesia\Models\Province::query()
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
                            ->options(function (Forms\Get $get): array {
                                $provinceName = $get('provinsi');
                                if (! $provinceName) {
                                    return [];
                                }

                                $provinceCode = \Laravolt\Indonesia\Models\Province::query()
                                    ->where('name', $provinceName)
                                    ->value('code');

                                if (! $provinceCode) {
                                    return [];
                                }

                                return \Laravolt\Indonesia\Models\City::query()
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
                            ->options(function (Forms\Get $get): array {
                                $cityName = $get('kota');
                                if (! $cityName) {
                                    return [];
                                }

                                $cityCode = \Laravolt\Indonesia\Models\City::query()
                                    ->where('name', $cityName)
                                    ->value('code');

                                if (! $cityCode) {
                                    return [];
                                }

                                return \Laravolt\Indonesia\Models\District::query()
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
                            ->options(function (Forms\Get $get): array {
                                $districtName = $get('kecamatan');
                                if (! $districtName) {
                                    return [];
                                }

                                $districtCode = \Laravolt\Indonesia\Models\District::query()
                                    ->where('name', $districtName)
                                    ->value('code');

                                if (! $districtCode) {
                                    return [];
                                }

                                return \Laravolt\Indonesia\Models\Village::query()
                                    ->where('district_code', $districtCode)
                                    ->orderBy('name')
                                    ->pluck('name', 'name')
                                    ->all();
                            })
                            ->placeholder('Pilih kelurahan/desa'),
                    ]),

                // Section 2: Status
                Section::make('Status')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktifkan Gudang')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false),
                    ]),

                // Section 3: Lokasi Map (BARU!)
                Section::make('Lokasi di Peta')
                    ->icon('heroicon-o-map-pin')
                    ->description('Klik dan drag marker untuk menentukan koordinat lokasi gudang.')
                    ->schema([
                        Map::make('location')
                            ->label('Pilih Lokasi')
                            ->columnSpanFull()
                            ->defaultLocation(latitude: -6.781672, longitude: 110.86482) // Default: Kudus
                            ->draggable()
                            ->clickable(true)
                            ->zoom(15)
                            ->showMarker()
                            ->tilesUrl('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
                            ->liveLocation(true, true, 5000) // Enable live location
                            ->showMyLocationButton()
                            ->extraStyles([
                                'min-height: 400px',
                                'border-radius: 8px',
                            ])
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (! $state || ! isset($state['lat']) || ! isset($state['lng'])) {
                                    return;
                                }

                                // Update latitude & longitude
                                $set('latitude', $state['lat'] ?? null);
                                $set('longitude', $state['lng'] ?? null);

                                // Reverse geocoding untuk mendapatkan alamat
                                try {
                                    $lat = $state['lat'];
                                    $lng = $state['lng'];

                                    // Gunakan Nominatim API (OpenStreetMap - GRATIS!)
                                    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";

                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, $url);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_USERAGENT, 'FilamentMapPicker/1.0');
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                                    $response = curl_exec($ch);
                                    curl_close($ch);

                                    if ($response) {
                                        $data = json_decode($response, true);

                                        if (isset($data['display_name'])) {
                                            // Hanya update jika field lokasi_gudang masih kosong
                                            // atau user belum mengisi manual
                                            $currentLokasi = $get('lokasi_gudang');
                                            if (empty($currentLokasi)) {
                                                $set('lokasi_gudang', $data['display_name']);
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    // Silently fail jika reverse geocoding error
                                }
                            })
                            ->afterStateHydrated(function ($state, $record, callable $set) {
                                // Load existing coordinates ke map saat edit
                                if ($record && $record->latitude && $record->longitude) {
                                    $set('location', [
                                        'lat' => (float) $record->latitude,
                                        'lng' => (float) $record->longitude,
                                    ]);
                                }
                            }),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->readOnly()
                                    ->placeholder('Otomatis dari peta'),
                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->readOnly()
                                    ->placeholder('Otomatis dari peta'),
                                TextInput::make('radius_km')
                                    ->label('Radius (Meter)')
                                    ->numeric()
                                    ->default(50)
                                    ->helperText('Radius area gudang dalam meter'),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Informasi Gudang')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        TextEntry::make('nama_gudang')
                            ->label('Nama Gudang')
                            ->weight('bold'),
                        TextEntry::make('lokasi_gudang')
                            ->label('Alamat'),
                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(3),

                InfolistSection::make('Lokasi di Peta')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        MapEntry::make('location')
                            ->label('')
                            ->state(fn ($record) => [
                                'lat' => (float) $record->latitude,
                                'lng' => (float) $record->longitude,
                            ])
                            ->columnSpanFull()
                            ->extraStyles([
                                'min-height: 300px',
                                'border-radius: 8px',
                            ]),
                        TextEntry::make('koordinat')
                            ->label('Koordinat')
                            ->state(fn ($record) => $record->latitude && $record->longitude
                                ? "{$record->latitude}, {$record->longitude}"
                                : '-')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('radius_km')
                            ->label('Radius')
                            ->suffix(' meter')
                            ->badge()
                            ->color('warning'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_gudang')
                    ->label('Nama Gudang')
                    ->weight('bold')
                    ->icon('heroicon-o-building-storefront')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('lokasi_gudang')
                    ->icon('heroicon-o-map')
                    ->label('Lokasi')
                    ->limit(25)
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Lat')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->label('Lng')
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListGudangs::route('/'),
            'create' => Pages\CreateGudang::route('/create'),
            'view' => Pages\ViewGudang::route('/{record}'),
            'edit' => Pages\EditGudang::route('/{record}/edit'),
        ];
    }
}
