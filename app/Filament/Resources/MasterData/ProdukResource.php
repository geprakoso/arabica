<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\ProdukResource\Pages;
use App\Filament\Exports\ProdukExporter;
use App\Models\Produk;
use Filament\Forms;
// use Filament\Forms\Components\Fieldset;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
// use Filament\Resources\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
// use Laravel\Pail\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Str; // Import Str
use Closure; // Import Closure for callable type hint
use Filament\Actions\Exports\Models\Export;
use Filament\Tables\Actions\ExportAction;

// use Laravel\SerializableClosure\Serializers\Native;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'hugeicons-package';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Split::make([
                    Section::make('Data Produk')
                        ->schema([
                            Forms\Components\TextInput::make('nama_produk')
                                ->label('Nama Produk') 
                                ->rules(['regex:/^[A-Z0-9\s]+$/'])
                                ->validationMessages([
                                    'regex' => 'Nama produk harus UPPERCASE.',
                                ])
                                ->required(),
                            Forms\Components\Select::make('kategori_id')
                                ->label('Kategori')
                                ->relationship('kategori', 'nama_kategori')
                                ->createoptionForm([
                                    Forms\Components\TextInput::make('nama_kategori')
                                        ->label('Nama Kategori')
                                        ->required(),
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\Select::make('brand_id')
                                ->label('Brand')
                                ->relationship('brand', 'nama_brand')
                                ->createoptionForm([
                                    Forms\Components\TextInput::make('nama_brand')
                                        ->label('Nama Brand')
                                        ->required(),
                                ])
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->default(fn () => Produk::generateSku())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->unique(ignoreRecord: true),
                        ]),
                        Section::make('Gambar Produk')
                            ->schema([
                        FileUpload::make('image_url')
                            ->label('Gambar Produk')
                            ->image()
                            ->disk('public')
                            ->directory(fn () => 'produks/' . now()->format('Y/m/d'))
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                $datePrefix = now()->format('ymd');
                                $slug = Str::slug($get('nama_produk') ?? 'produk');
                                $extension = $file->getClientOriginalExtension();
                                return "{$datePrefix}-{$slug}.{$extension}";
                            })
                            ->preserveFilenames()
                            ->nullable(),
                        ]),
                    ])->from('lg')
                        ->columnSpanFull(),
                //

                Tabs::make('Spesifikasi Produk')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Detail Produk')
                            ->schema([
                                Forms\Components\TextInput::make('berat')
                                    ->label('Berat (gr)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                Forms\Components\TextInput::make('panjang')
                                    ->label('Panjang (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                Forms\Components\TextInput::make('lebar')
                                    ->label('Lebar (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                                Forms\Components\TextInput::make('tinggi')
                                    ->label('Tinggi (cm)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
                            ]),
                        Tab::make('Deskripsi Produk')
                            ->schema([
                                Forms\Components\RichEditor::make('deskripsi')
                                    ->label('Deskripsi')
                                    ->nullable(),
                            ]),
                        ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.nama_brand')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
                SelectFilter::make('kategori')->relationship('kategori', 'nama_kategori')->native(false),
                SelectFilter::make('brand')->relationship('brand', 'nama_brand')->native(false),
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
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'view' => Pages\ViewProduk::route('/{record}'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }
}
