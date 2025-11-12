<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JasaResource\Pages;
use App\Filament\Resources\JasaResource\RelationManagers;
use App\Models\Jasa;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str; // Import Str
use Closure; // Import Closure for callable type hint
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile as LivewireTemporaryUploadedFile;

class JasaResource extends Resource
{
    protected static ?string $model = Jasa::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Jasa';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Fieldset::make('Detail Jasa')
                    ->schema([
                        Forms\Components\TextInput::make('nama_jasa')
                            ->label('Nama Jasa')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU Jasa')
                            ->default(fn () => Jasa::generateSku())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true),
                        FileUpload::make('image_url')
                            ->label('Gambar Jasa')
                            ->image()
                            ->disk('public')
                            ->directory(fn () => 'jasas/' . now()->format('Y/m/d'))
                            ->getUploadedFileNameForStorageUsing(function (LivewireTemporaryUploadedFile $file, Get $get): string {
                                $datePrefix = now()->format('ymd');
                                $slug = Str::slug($get('nama_jasa') ?? 'jasa');
                                $extension = $file->getClientOriginalExtension();
                                return "{$datePrefix}-{$slug}.{$extension}";
                            })
                            ->preserveFilenames()
                            ->nullable(),
                    ]),

                Fieldset::make('Harga')
                    ->schema([
                        Forms\Components\TextInput::make('harga')
                            ->label('Harga Jasa')
                            ->numeric()
                            ->required(),
                    ]),

                Fieldset::make('Detail')
                    ->columns(1)
                    ->schema([
                        TimePicker::make('estimasi_waktu_jam')
                            ->label('Estimasi Waktu Penyelesaian')
                            ->required()
                            ->seconds(false)
                            ->prefix('Durasi')
                            ->dehydrateStateUsing(fn (?string $state) => $state ? Carbon::createFromFormat('H:i', $state)->hour : null)
                            ->afterStateHydrated(fn (TimePicker $component, $state) => $component->state($state !== null ? sprintf('%02d:00', $state) : null)),
                        Forms\Components\RichEditor::make('deskripsi')
                        ->label('Deskripsi Jasa')
                        ->nullable(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('nama_jasa')
                    ->label('Nama Jasa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('harga')
                    ->label('Harga')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('estimasi_waktu_jam')
                    ->label('Estimasi Waktu (Jam)')
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
            'index' => Pages\ListJasas::route('/'),
            'create' => Pages\CreateJasa::route('/create'),
            'view' => Pages\ViewJasa::route('/{record}'),
            'edit' => Pages\EditJasa::route('/{record}/edit'),
        ];
    }
}
