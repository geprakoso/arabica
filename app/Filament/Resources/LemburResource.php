<?php

namespace App\Filament\Resources;

use App\Enums\StatusPengajuan;
use App\Filament\Resources\LemburResource\Pages;
use App\Filament\Resources\LemburResource\RelationManagers;
use App\Models\Lembur;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Date;

class LemburResource extends Resource
{
    protected static ?string $model = Lembur::class;

    protected static ?string $navigationIcon = 'hugeicons-clock-05';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?string $navigationLabel = 'Lembur'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Detail Pengajuan')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Pilih karyawan')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Status Pengajuan')
                            ->options(StatusPengajuan::class)
                            ->default(StatusPengajuan::Pending)
                            ->live()
                            ->required(),
                        TextInput::make('keperluan')
                            ->label('Keperluan')
                            ->placeholder('Misal, lembur untuk mengerjakan rakitan user Ali')
                            ->required(),
                    ])->columnSpanFull(),

                Section::make('Detail Waktu')
                        ->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal lembur')
                                ->required()
                                ->default('today'),
                            TimePicker::make('jam_mulai')
                                ->label('jam mulai')
                                ->required()
                                ->seconds(false)
                                ->default('now'),
                            TimePicker::make('jam_selesai')
                                ->label('Jam selesai')
                                ->seconds(false)
                                ->nullable(),
                        ]),

                Section::make('Catatan')
                        ->schema([
                            TextInput::make('catatan')
                                ->label('Catatan')
                                ->nullable()
                        ])
                        ->visible(fn (Get $get) => in_array($get('status'), [
                            StatusPengajuan::Diterima->value,
                            StatusPengajuan::Ditolak->value,
                        ])),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Detail Pengajuan')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Karyawan'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d M Y'),
                        TextEntry::make('jam_mulai')
                            ->label('Mulai')
                            ->dateTime('H:i'),
                        TextEntry::make('jam_selesai')
                            ->label('Selesai')
                            ->dateTime('H:i')
                            ->placeholder('-'),
                        TextEntry::make('keperluan')
                            ->columnSpanFull()
                            ->label('Keperluan')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                InfolistSection::make('Status')
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                                ? $state->getLabel()
                                : (filled($state) ? StatusPengajuan::from($state)->getLabel() : null))
                            ->color(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                                ? $state->getColor()
                                : (filled($state) ? StatusPengajuan::from($state)->getColor() : null)),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->hidden(fn ($record) => blank($record->catatan))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getLabel()
                        : (filled($state) ? StatusPengajuan::from($state)->getLabel() : null))
                    ->color(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getColor()
                        : (filled($state) ? StatusPengajuan::from($state)->getColor() : null))
                    ->sortable(),
                TextColumn::make('keperluan')
                    ->limit(40)
                    ->wrap(),            
                    ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListLemburs::route('/'),
            'create' => Pages\CreateLembur::route('/create'),
            'edit' => Pages\EditLembur::route('/{record}/edit'),
            'view' => Pages\ViewLembur::route('/{record}'),
        ];
    }
}
