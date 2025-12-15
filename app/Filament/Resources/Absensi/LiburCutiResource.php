<?php

namespace App\Filament\Resources\Absensi;

use App\Filament\Resources\Absensi\LiburCutiResource\Pages;
use App\Models\LiburCuti;
use App\Models\Karyawan;
use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;

class LiburCutiResource extends Resource
{
    protected static ?string $model = LiburCuti::class;

    protected static ?string $navigationIcon = 'hugeicons-sailboat-offshore';
    protected static ?string $navigationGroup = 'Absensi';

    public static function canViewAny(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();

        return $user?->can('view_any_libur_cuti')
            || $user?->can('view_any_absensi::libur::cuti') // format lama
            || $user?->can('view_limit_libur_cuti')
            || $user?->can('view_limit_absensi::libur::cuti') // format lama
            || false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query;
        }

        if ($user->hasRole('karyawan')) {
            return $query->where('user_id', $user->id);
        }

        // Jika hanya punya izin view_limit, batasi ke data milik sendiri.
        if (
            (
                $user?->can('view_limit_libur_cuti') ||
                $user?->can('view_limit_absensi::libur::cuti')
            ) && ! (
                $user?->can('view_any_libur_cuti') ||
                $user?->can('view_any_absensi::libur::cuti')
            )
        ) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2) // Grid 2 kolom
            ->schema([

                // === KOLOM KIRI (DATA PENGAJUAN) ===
                Group::make()
                    ->columnSpanFull()
                    ->schema([

                        // Section 1: Detail Identitas & Alasan
                        Section::make('Informasi Pengajuan')
                            ->description('Pilih karyawan dan jenis keperluan cuti.')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Nama Karyawan')
                                    ->options(function () {
                                        $query = Karyawan::query()
                                            ->whereNotNull('user_id');

                                        $user = Auth::user();

                                        if ($user && $user->hasRole('karyawan')) {
                                            $query->where('user_id', $user->id);
                                        }

                                        return $query->pluck('nama_karyawan', 'user_id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::id())
                                    ->disabled(fn() => Auth::user()?->hasRole('karyawan'))
                                    ->required()
                                    ->columnSpanFull(),

                                ToggleButtons::make('keperluan')
                                    ->label('Jenis Keperluan')
                                    ->options(Keperluan::class)
                                    ->inline()
                                    ->required()
                                    ->columnSpanFull()
                                    ->columns([
                                        'default' => 2,
                                        'sm' => 3,
                                        'xl' => 4,
                                    ]), // Agar tombol tertata rapi dalam grid

                                Textarea::make('keterangan')
                                    ->label('Keterangan Tambahan')
                                    ->placeholder('Contoh: Acara keluarga di luar kota...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Jadwal
                        Section::make('Durasi Cuti')
                            ->icon('heroicon-m-calendar-days')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('mulai_tanggal')
                                    ->label('Tanggal Mulai')
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->required(),

                                DatePicker::make('sampai_tanggal')
                                    ->label('Sampai Tanggal')
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->afterOrEqual('mulai_tanggal') // Validasi logis
                                    ->required(),
                            ]),
                    ]),
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
                    ->formatStateUsing(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                        ? $state->getLabel()
                        : (filled($state) ? Keperluan::from($state)->getLabel() : null))
                    ->color(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                        ? $state->getColor()
                        : (filled($state) ? Keperluan::from($state)->getColor() : null))
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
                    ->formatStateUsing(fn(StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getLabel()
                        : (filled($state) ? StatusPengajuan::from($state)->getLabel() : null))
                    ->color(fn(StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getColor()
                        : (filled($state) ? StatusPengajuan::from($state)->getColor() : null))
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
                            ->mapWithKeys(fn(Keperluan $case) => [$case->value => $case->getLabel()])
                            ->all()
                    ),
                SelectFilter::make('status_pengajuan')
                    ->options(
                        collect(StatusPengajuan::cases())
                            ->mapWithKeys(fn(StatusPengajuan $case) => [$case->value => $case->getLabel()])
                            ->all()
                    ),
                Filter::make('periode')
                    ->form([
                        DatePicker::make('mulai')->label('Mulai dari'),
                        DatePicker::make('sampai')->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['mulai'] ?? null, fn($q, $date) => $q->whereDate('mulai_tanggal', '>=', $date))
                            ->when($data['sampai'] ?? null, fn($q, $date) => $q->whereDate('mulai_tanggal', '<=', $date));
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
            'view' => Pages\ViewLiburCuti::route('/{record}'),
        ];
    }
}
