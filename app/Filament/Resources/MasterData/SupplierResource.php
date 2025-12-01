<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\SupplierResource\Pages;
// use App\Filament\Resources\MasterData\SupplierResource\RelationManagers;
use App\Filament\Resources\MasterData\SupplierResource\RelationManagers\AgentsRelationManager;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope;
// use Dom\Text;
use Filament\Tables\Columns\TextColumn;
// use Ramsey\Uuid\Type\Time;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-truck';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationParentItem = 'User';
    protected static ?string $navigationLabel = 'Supplier';
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
                            ->schema([
                                Forms\Components\TextInput::make('provinsi')
                                    ->label('Provinsi')
                                    ->placeholder('Jawa Barat'),
                                    
                                Forms\Components\TextInput::make('kota')
                                    ->label('Kota / Kabupaten')
                                    ->placeholder('Bandung'),
                                    
                                Forms\Components\TextInput::make('kecamatan')
                                    ->label('Kecamatan')
                                    ->placeholder('Cicendo'),
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
