<?php

namespace App\Filament\Resources\Akunting;

use App\Filament\Resources\Akunting\JenisAkunResource\Pages;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class JenisAkunResource extends Resource
{
    protected static ?string $model = JenisAkun::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationParentItem = 'Input Transaksi Toko';
    protected static ?string $navigationLabel = '2. Jenis Akun';
    protected static ?string $pluralLabel = 'Jenis Akun';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 2;
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Akun')
                    ->schema([
                        Select::make('kode_akun_id')
                            ->label('Kode Akun (Prefix)')
                            ->relationship('kodeAkun', 'kode_akun')
                            ->getOptionLabelFromRecordUsing(fn (KodeAkun $record) => "{$record->kode_akun} - {$record->nama_akun}")
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->disabledOn('edit')
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if (blank($state)) {
                                    $set('kode_jenis_akun', null);
                                    return;
                                }

                                $set('kode_jenis_akun', JenisAkun::generateKodeJenisAkun((int) $state));
                            })
                            ->columnSpanFull(),

                        TextInput::make('kode_jenis_akun')
                            ->label('Kode Jenis Akun')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Otomatis: prefix dari Kode Akun + nomor urut.')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('nama_jenis_akun')
                            ->label('Nama Jenis Akun')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2), // Mengatur layout menjadi 2 kolom
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_jenis_akun')
                    ->label('Kode Akun')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_jenis_akun')
                    ->label('Nama Jenis Akun')
                    ->sortable()
                    ->searchable(),
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
                    ->url(fn (JenisAkun $record) => static::getUrl(
                        'view',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
                Tables\Actions\EditAction::make()
                    ->url(fn (JenisAkun $record) => static::getUrl(
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
                                fn (JenisAkun $record): bool => $record->input_transaksi_tokos_count > 0
                            );

                            if ($blocked->isEmpty()) {
                                return;
                            }

                            $blockedCount = $blocked->sum('input_transaksi_tokos_count');
                            Notification::make()
                                ->title('Tidak bisa hapus jenis akun')
                                ->body("Masih ada {$blockedCount} transaksi terkait di Input Transaksi Toko. Hapus transaksi tersebut terlebih dahulu.")
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

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['kode_jenis_akun'] = JenisAkun::generateKodeJenisAkun($data['kode_akun_id'] ?? null);

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJenisAkun::route('/'),
            'create' => Pages\CreateJenisAkun::route('/create'),
            'view' => Pages\ViewJenisAkun::route('/{record}'),
            'edit' => Pages\EditJenisAkun::route('/{record}/edit'),
        ];
    }
}
