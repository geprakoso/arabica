<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JasaResource\Pages;
use App\Filament\Resources\JasaResource\RelationManagers;
use App\Models\Jasa;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
                        Forms\Components\TextInput::make('kode_jasa')
                            ->label('Kode Jasa')
                            ->required()
                            ->unique(),
                        FileUpload::make('image_url')
                            ->label('Gambar Jasa')
                            ->image()
                            ->disk('public')
                            ->directory(fn () => 'jasas/' . now()->format('Y/m/d'))
                            ->getUploadedFileNameForStorageUsing(function (LivewireTemporaryUploadedFile $file, Closure $set, Closure $get) {
                                $datePrefix = now()->format('ymd');
                                $slug = Str::slug($get('nama_jasa') ?? 'jasa');
                                $extension = $file->getClientOriginalExtension();
                                return "{$datePrefix}-{$slug}." . $extension;
                            })
                            ->preserveFilenames()
                            ->required(),
                    ]),

                Fieldset::make('Harga')
                    ->schema([
                        Forms\Components\TextInput::make('harga')
                            ->label('Harga Jasa')
                            ->numeric()
                            ->required(),
                    ]),

                Fieldset::make('Detail')
                    ->schema([
                        TimePicker::make('estimasi_waktu')
                            ->label('Estimasi Waktu Penyelesaian')
                            ->required()
                            ->seconds(false)
                            ->prefix('Durasi'),
                        Forms\Components\Textarea::make('deskripsi')
                        ->label('Deskripsi Jasa')
                        ->rows(3),
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
