<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\BrandResource\Pages;
use App\Filament\Resources\MasterData\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str; // Import Str
use Closure; // Import Closure for callable type hint
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Brand';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make('Detail Brand')
                        ->schema([
                            Forms\Components\TextInput::make('nama_brand')
                                ->label('Nama Brand')
                                ->required(),
                            Forms\Components\TextInput::make('slug')
                                ->label('Slug')
                                ->unique(ignoreRecord: true),
                            Toggle::make('is_active')
                                ->label('Aktifkan Brand')
                                ->default(true)
                                ->required(),
                        ]),
                    Section::make('Detail Brand')
                        ->schema([
                            Forms\Components\FileUpload::make('logo_url')
                                ->label('Gambar Produk')
                                ->image()
                                ->disk('public')
                                ->directory(fn () => 'produks/' . now()->format('Y/m/d'))
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                    $datePrefix = now()->format('ymd');
                                    $slug = Str::slug($get('nama_brand') ?? 'brand');
                                    $extension = $file->getClientOriginalExtension();
                                    return "{$datePrefix}-{$slug}.{$extension}";
                                })
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
                //
                TextColumn::make('nama_brand')
                    ->label('Nama Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Aktif')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Aktif' : 'Nonaktif')
                    ->color(fn (bool $state) => $state ? 'success' : 'danger')
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
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record}'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
