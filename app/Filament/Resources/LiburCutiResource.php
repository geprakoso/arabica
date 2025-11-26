<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiburCutiResource\Pages;
use App\Models\LiburCuti;
use App\Models\Karyawan;
use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class LiburCutiResource extends Resource
{
    protected static ?string $model = LiburCuti::class;

    protected static ?string $navigationIcon = 'hugeicons-sailboat-offshore';
    protected static ?string $navigationGroup = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->label('Karyawan')
                    ->options(
                        Karyawan::query()
                            ->whereNotNull('user_id')
                            ->pluck('nama_karyawan', 'user_id')
                    )
                    ->searchable()
                    ->default(fn () => auth()->id())
                    ->required(),

                ToggleButtons::make('keperluan')
                    ->inline()
                    ->options(Keperluan::class)
                    ->required(),

                DatePicker::make('mulai_tanggal')
                    ->label('Mulai')
                    ->required(),

                DatePicker::make('sampai_tanggal')
                    ->label('Sampai')
                    ->afterOrEqual('mulai_tanggal'),

                Select::make('status_pengajuan')
                    ->options(StatusPengajuan::class)
                    ->default(StatusPengajuan::Pending)
                    ->required(),

                Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keperluan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => filled($state) ? Keperluan::from($state)->getLabel() : null)
                    ->color(fn (?string $state) => filled($state) ? Keperluan::from($state)->getColor() : null)
                    ->sortable(),
                TextColumn::make('mulai_tanggal')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable()
                    ->default('today'),
                TextColumn::make('sampai_tanggal')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status_pengajuan')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => filled($state) ? StatusPengajuan::from($state)->getLabel() : null)
                    ->color(fn (?string $state) => filled($state) ? StatusPengajuan::from($state)->getColor() : null)
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('keperluan')
                    ->options(
                        collect(Keperluan::cases())
                            ->mapWithKeys(fn (Keperluan $case) => [$case->value => $case->getLabel()])
                            ->all()
                    ),
                SelectFilter::make('status_pengajuan')
                    ->options(
                        collect(StatusPengajuan::cases())
                            ->mapWithKeys(fn (StatusPengajuan $case) => [$case->value => $case->getLabel()])
                            ->all()
                    ),
                Filter::make('periode')
                    ->form([
                        DatePicker::make('mulai')->label('Mulai dari'),
                        DatePicker::make('sampai')->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['mulai'] ?? null, fn ($q, $date) => $q->whereDate('mulai_tanggal', '>=', $date))
                            ->when($data['sampai'] ?? null, fn ($q, $date) => $q->whereDate('mulai_tanggal', '<=', $date));
                    }),
            ])
            ->actions([
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
            'index' => Pages\ListLiburCutis::route('/'),
            'create' => Pages\CreateLiburCuti::route('/create'),
            'edit' => Pages\EditLiburCuti::route('/{record}/edit'),
        ];
    }
}