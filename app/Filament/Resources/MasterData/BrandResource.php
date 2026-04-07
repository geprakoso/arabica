<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\BrandResource\Pages;
// use App\Filament\Resources\MasterData\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str; // Import Str
// use Closure; // Import Closure for callable type hint
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Support\WebpUpload;


class BrandResource extends BaseResource
{
    protected static ?string $model = Brand::class;
    // protected static ?string $cluster = MasterData::class;
    // protected static ?string $navigationIcon = 'hugeicons-label-important';
    protected static ?string $navigationParentItem = 'Produk & Kategori';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?String $pluralLabel = 'Brand';
    protected static ?string $navigationLabel = 'Brand';
    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'nama_brand';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_brand', 'slug'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make('Detail Brand')
                        ->schema([
                            Forms\Components\TextInput::make('nama_brand')
                                ->label('Nama Brand')
                                ->dehydrateStateUsing(fn($state) => Str::title($state))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state) {
                                    $set('slug', Str::slug($state));
                                })
                                ->rules([
                                    fn (\Filament\Forms\Get $get, ?Brand $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $slug = Str::slug($value);
                                        $query = Brand::where('slug', $slug);
                                        
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }
                                        
                                        if ($query->exists()) {
                                            $fail('Brand dengan nama ini sudah ada. Silakan gunakan nama yang berbeda.');
                                        }
                                    },
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->dehydrateStateUsing(fn($state) => Str::slug($state))
                                ->live(onBlur: true),
                            Toggle::make('is_active')
                                ->label('Aktifkan Brand')
                                ->default(true)
                                ->required()
                                ->hidden(),
                        ]),
                    Section::make('Detail Brand')
                        ->schema([
                            Forms\Components\FileUpload::make('logo_url')
                                ->label('Gambar Produk')
                                ->image()
                                ->disk('public')
                                ->imageEditor()
                                ->imageCropAspectRatio('1:1')
                                ->imageResizeTargetWidth(800)
                                ->imageResizeTargetHeight(800)
                                ->directory(fn() => 'produks/' . now()->format('Y/m/d'))
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                    $datePrefix = now()->format('ymd');
                                    $slug = Str::slug($get('nama_brand') ?? 'brand');
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$datePrefix}-{$slug}.{$extension}";
                                })
                                ->saveUploadedFileUsing(fn(BaseFileUpload $component, TemporaryUploadedFile $file): ?string => WebpUpload::store($component, $file))
                                ->preserveFilenames()
                                ->nullable(),
                        ]),
                ])->from('lg')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('logo_url')
                        ->height('100%')
                        ->width('100%')
                        ->disk('public')
                        ->defaultImageUrl(url('/images/icons/icon-256x256.png'))
                        ->extraImgAttributes(['class' => 'object-contain h-32 w-full bg-white rounded-t-lg mb-2']),

                    Tables\Columns\Layout\Stack::make([
                        TextColumn::make('nama_brand')
                            ->weight('bold')
                            ->label('Nama Brand')
                            ->formatStateUsing(fn($state) => Str::upper($state))
                            ->size(TextColumn\TextColumnSize::Large)
                            ->searchable()
                            ->alignCenter(),

                        TextColumn::make('slug')
                            ->icon('heroicon-m-link')
                            ->iconColor('gray')
                            ->color('gray')
                            ->searchable()
                            ->size(TextColumn\TextColumnSize::Small)
                            ->alignCenter(),
                    ])->space(1),
                ])->space(3),
            ])
            ->searchable(true)
            ->contentGrid([
                'md' => 3,
                'xl' => 4,
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\EditAction::make()->color('warning'),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->tooltip('Aksi'),
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
