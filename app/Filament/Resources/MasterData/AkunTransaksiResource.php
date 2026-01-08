<?php

namespace App\Filament\Resources\MasterData;

use App\Filament\Resources\MasterData\AkunTransaksiResource\Pages;
use App\Filament\Resources\MasterData\AkunTransaksiResource\RelationManagers;
use App\Models\AkunTransaksi;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\SubNavigationPosition;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AkunTransaksiResource extends BaseResource
{
    protected static ?string $model = AkunTransaksi::class;

    // protected static ?string $cluster = MasterData::class;
    protected static ?string $navigationIcon = 'hugeicons-wallet-01';
    protected static ?string $navigationLabel = "Akun Transaksi";
    // protected static ?string $navigationParentItem = 'Master Data';
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Akun')
                    ->schema([
                        Forms\Components\TextInput::make('nama_akun')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('kode_akun')
                            ->label('Kode Akun')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Otomatis digenerate')
                            ->maxLength(255),
                        Forms\Components\Select::make('jenis')
                            ->options([
                                'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                                'qris' => 'QRIS',
                                'e-wallet' => 'E-Wallet',
                                'gyro' => 'Gyro',
                            ])
                            ->required()
                            ->live()
                            ->native(false)
                            ->default('transfer'),
                    ]),

                Section::make('Data Rekening Bank')
                    ->compact()
                    ->visible(fn (Get $get) => $get('jenis') === 'transfer')
                    ->schema([
                        Forms\Components\TextInput::make('nama_bank')
                            ->label('Nama Bank')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nama_rekening')
                            ->label('Nama Rekening')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('no_rekening')
                            ->label('No. Rekening')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

                Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),

                Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_akun')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode_akun')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'transfer' => 'info',
                        'tunai' => 'success',
                        'qris' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'tunai' => 'Tunai',
                        'transfer' => 'Transfer',
                        'qris' => 'QRIS',
                        'e-wallet' => 'E-Wallet',
                        'gyro' => 'Gyro',
                    ])
                    ->native(false),
                TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-aktif')
                    ->native(false),
                TernaryFilter::make('nama_bank')
                    ->label('Punya Bank?')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('nama_bank'),
                        false: fn (Builder $query) => $query->whereNull('nama_bank'),
                    )
                    ->native(false),
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
            'index' => Pages\ListAkunTransaksis::route('/'),
            'create' => Pages\CreateAkunTransaksi::route('/create'),
            'view' => Pages\ViewAkunTransaksi::route('/{record}'),
            'edit' => Pages\EditAkunTransaksi::route('/{record}/edit'),
        ];
    }
}
