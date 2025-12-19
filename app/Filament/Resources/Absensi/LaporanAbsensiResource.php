<?php

namespace App\Filament\Resources\Absensi;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use App\Filament\Resources\Absensi\LaporanAbsensiResource\Pages;
use App\Models\Absensi;
use Carbon\Carbon;
use Dotenv\Util\Str;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanAbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Absensi';

    protected static ?string $navigationLabel = 'Laporan Absensi';

    protected static ?string $pluralModelLabel = 'Laporan Absensi';

    protected static ?int $navigationSort = 3;


    // public static function canCreate(): bool
    // {
    //     return false;
    // }

    // public static function canEdit($record): bool
    // {
    //     return false;
    // }

    // public static function canDelete($record): bool
    // {
    //     return false;
    // }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query, HasTable $livewire): void {
                $hasManualSort = filled(data_get($livewire, 'tableSortColumn'));

                if (self::isDetailTabActive($livewire)) {
                    $query->with('user');

                    if (! $hasManualSort) {
                        $query->orderByDesc('tanggal');
                    }

                    return;
                }

                $query
                    ->selectRaw('MIN(id) as id')
                    ->addSelect('user_id')
                    ->selectRaw("SUM(CASE WHEN LOWER(status) = 'hadir' THEN 1 ELSE 0 END) as total_hadir")
                    ->selectRaw("SUM(CASE WHEN LOWER(status) = 'izin' THEN 1 ELSE 0 END) as total_izin")
                    ->selectRaw("SUM(CASE WHEN LOWER(status) = 'sakit' THEN 1 ELSE 0 END) as total_sakit")
                    ->selectRaw('COUNT(*) as total_absen')
                    ->groupBy('user_id')
                    ->with('user');

                if (! $hasManualSort) {
                    $query->orderByDesc('total_absen');
                }
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_hadir')
                    ->label('Hadir')
                    ->numeric()
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_izin')
                    ->label('Izin')
                    ->numeric()
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_sakit')
                    ->label('Sakit')
                    ->numeric()
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_absen')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                BadgeableColumn::make('jam_masuk')
                    ->label('Jam Kehadiran')
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->formatStateUsing(fn (?string $state): string => self::getJamMasukMeta($state)['display'])
                    ->color(fn (?string $state): string => self::getJamMasukMeta($state)['color'] ?? 'white')
                    ->suffixBadges(fn (Model $record): array => self::getTelatBadges($record->jam_masuk)),
                TextColumn::make('jam_keluar')
                    ->label('Jam Keluar')
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->date('H:i')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::createFromFormat('H:i:s', $state)->format('H:i') : '-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => str($state ?? '-')->title())
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'izin',
                        'danger' => 'sakit',
                        'gray' => 'alpha',
                    ])
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->toggleable()
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
            ])
            ->filters([
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(self::monthOptions())
                    ->default(Carbon::now()->format('Y-m'))
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        [$year, $month] = explode('-', $value);

                        return $query
                            ->whereYear('tanggal', (int) $year)
                            ->whereMonth('tanggal', (int) $month);
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    } 

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanAbsensis::route('/'),
        ];
    }

    protected static function getJamMasukMeta(?string $state): array
    {
        $default = [
            'display' => '-',
            // 'color' => 'white',
            'lateMinutes' => null,
        ];

        if (blank($state)) {
            return $default;
        }

        $batas = Carbon::createFromTimeString('10:00:00');
        $masuk = Carbon::createFromFormat('H:i:s', $state);
        $display = $masuk->format('H:i');

        if ($masuk->lte($batas)) {
            return [
                'display' => $display,
                // 'color' => 'success',
                'lateMinutes' => null,
            ];
        }

        $telatMenit = $batas->diffInMinutes($masuk);

        return [
            'display' => $display,
            // 'color' => $telatMenit <= 30 ? 'warning' : 'danger',
            'lateMinutes' => $telatMenit,
        ];
    }

    protected static function getTelatBadges(?string $state): array
    {
        $meta = self::getJamMasukMeta($state);

        if (! $meta['lateMinutes']) {
            return [];
        }

        $telatMenit = $meta['lateMinutes'];

        return [
            Badge::make('telat-menit')
                ->label("Telat {$telatMenit}m")
                ->color($telatMenit <= 30 ? 'warning' : 'danger'),
        ];
    }

    protected static function monthOptions(): array
    {
        $now = Carbon::now();

        return collect(range(1, 12))
            ->mapWithKeys(function (int $month) use ($now): array {
                $date = $now->copy()->month($month);

                return [
                    $date->format('Y-m') => $date->translatedFormat('F Y'),
                ];
            })
            ->toArray();
    }

    protected static function isDetailTabActive(?HasTable $livewire = null): bool
    {
        return data_get($livewire, 'activeTab') === 'rincian';
    }
}
