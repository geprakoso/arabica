<?php

namespace App\Filament\Resources\Akunting;

use App\Enums\KategoriAkun; // Sesuaikan namespace Enum Anda
use App\Enums\KelompokNeraca;
use App\Filament\Resources\Akunting\KodeAkunResource\Pages;
use App\Models\KodeAkun; // Sesuaikan namespace Model Anda
use Filament\Facades\Filament;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class KodeAkunResource extends BaseResource
{
    protected static ?string $model = KodeAkun::class;
    protected static ?string $navigationLabel = 'Kode Akun';
    protected static ?string $pluralLabel = 'Kode Akun';
    protected static ?string $navigationIcon = 'hugeicons-bar-code-02';
    protected static ?string $navigationParentItem = 'Akun Keuangan';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && static::canViewAny();
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Data Akun')
                    ->schema([
                        TextInput::make('kode_akun')
                            ->label('Kode Akun')
                            ->required()
                            ->maxLength(255)
                            ->unique(KodeAkun::class, 'kode_akun', ignoreRecord: true),

                        TextInput::make('nama_akun')
                            ->label('Nama Akun')
                            ->required()
                            ->maxLength(255),

                        Select::make('kategori_akun')
                            ->label('Kategori Akun')
                            ->options(KategoriAkun::class)
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kategori')
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (! in_array($state, [KategoriAkun::Aktiva->value, KategoriAkun::Pasiva->value], true)) {
                                    $set('kelompok_neraca', null);
                                }
                            })
                            ->columnSpanFull(),

                        Select::make('kelompok_neraca')
                            ->label('Kelompok Neraca')
                            ->options(KelompokNeraca::class)
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kelompok neraca')
                            ->visible(fn (Get $get): bool => in_array($get('kategori_akun'), [KategoriAkun::Aktiva->value, KategoriAkun::Pasiva->value], true))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_akun')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama_akun')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategori_akun')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => KategoriAkun::tryFrom($state)?->getLabel() ?? '-')
                    ->color(fn (?string $state) => KategoriAkun::tryFrom($state)?->getColor()),
                TextColumn::make('kelompok_neraca')
                    ->label('Kelompok Neraca')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof KelompokNeraca ? $state : KelompokNeraca::tryFrom((string) $state);

                        return $enum?->getLabel() ?? '-';
                    })
                    ->color(function ($state): ?string {
                        $enum = $state instanceof KelompokNeraca ? $state : KelompokNeraca::tryFrom((string) $state);

                        return $enum?->getColor();
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (KodeAkun $record) => static::getUrl(
                        'view',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
                Tables\Actions\EditAction::make()
                    ->url(fn (KodeAkun $record) => static::getUrl(
                        'edit',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records): void {
                            $records->loadCount('inputTransaksiTokos');
                            $blocked = $records->filter(
                                fn (KodeAkun $record): bool => $record->input_transaksi_tokos_count > 0
                            );

                            if ($blocked->isEmpty()) {
                                return;
                            }

                            $blockedCount = $blocked->sum('input_transaksi_tokos_count');
                            Notification::make()
                                ->title('Tidak bisa hapus kode akun')
                                ->body("Masih ada {$blockedCount} transaksi terkait di Input Transaksi Toko. Hapus transaksi atau jenis akun terkait terlebih dahulu.")
                                ->danger()
                                ->send();

                            $action->halt();
                        }),
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
            'index' => Pages\ListKodeAkuns::route('/'),
            'create' => Pages\CreateKodeAkun::route('/create'),
            'view' => Pages\ViewKodeAkun::route('/{record}'),
            'edit' => Pages\EditKodeAkun::route('/{record}/edit'),
        ];
    }
}
