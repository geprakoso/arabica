<?php

namespace App\Filament\Resources\Absensi;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use App\Filament\Resources\Absensi\LaporanAbsensiResource\Pages;
use App\Models\Absensi;
use Carbon\Carbon;
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
                    ->weight('bold')
                    ->description(fn(Model $record) => $record->user->email ?? '-')
                    ->icon('heroicon-m-user')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_hadir')
                    ->label('Hadir')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn(HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_izin')
                    ->label('Izin')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-document-text')
                    ->visible(fn(HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_sakit')
                    ->label('Sakit')
                    ->numeric()
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-face-frown')
                    ->visible(fn(HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_absen')
                    ->label('Total Absen')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-calculator')
                    ->numeric()
                    ->sortable()
                    ->visible(fn(HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn(Model $record) => $record->tanggal->locale('id')->translatedFormat('l'))
                    ->icon('heroicon-m-calendar')
                    ->sortable()
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                BadgeableColumn::make('jam_masuk')
                    ->label('Jam Kehadiran')
                    ->iconColor('success')
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->formatStateUsing(fn(?string $state): string => self::getJamMasukMeta($state)['display'])
                    ->color(fn(?string $state): string => self::getJamMasukMeta($state)['color'] ?? 'white')
                    ->icon('heroicon-m-arrow-right-end-on-rectangle')
                    ->suffixBadges(fn(Model $record): array => self::getTelatBadges($record->jam_masuk)),
                TextColumn::make('jam_keluar')
                    ->label('Jam Keluar')
                    ->iconColor('danger')
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->date('H:i')
                    ->icon('heroicon-m-arrow-left-start-on-rectangle')
                    ->formatStateUsing(fn(?string $state): string => $state ? Carbon::createFromFormat('H:i:s', $state)->format('H:i') : '-')
                    ->placeholder('-'),
                TextColumn::make('jam_kerja')
                    ->label('Durasi Kerja')
                    ->icon('heroicon-m-clock')
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->state(fn(Model $record): string => self::formatJamKerjaLabel($record->jam_masuk, $record->jam_keluar)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => str($state ?? '-')->title())
                    ->colors([
                        'success' => 'hadir',
                        'warning' => 'izin',
                        'danger' => 'sakit',
                        'gray' => 'alpha',
                    ])
                    ->icon(fn(string $state): string => match ($state) {
                        'hadir' => 'heroicon-m-check-circle',
                        'izin' => 'heroicon-m-document-text',
                        'sakit' => 'heroicon-m-plus-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->visible(fn(HasTable $livewire): bool => self::isDetailTabActive($livewire)),
            ])
            ->filters([
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(self::monthOptions())
                    ->native(false)
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
                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
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
                'lateMinutes' => null,
            ];
        }

        $telatMenit = $batas->diffInMinutes($masuk);

        return [
            'display' => $display,
            'lateMinutes' => $telatMenit,
        ];
    }

    protected static function getTelatBadges(?string $state): array
    {
        $meta = self::getJamMasukMeta($state);

        if (! $meta['lateMinutes']) {
            return [];
        }

        $telatMenit = (int) $meta['lateMinutes'];

        return [
            Badge::make('telat-menit')
                ->label(self::formatTelatLabel($telatMenit))
                ->color($telatMenit <= 30 ? 'warning' : 'danger'),
        ];
    }

    protected static function formatTelatLabel(int $telatMenit): string
    {
        if ($telatMenit < 60) {
            return "Telat {$telatMenit} menit";
        }

        $hours = intdiv($telatMenit, 60);
        $minutes = $telatMenit % 60;

        if ($minutes === 0) {
            return "Telat {$hours} jam";
        }

        return "Telat {$hours} jam {$minutes} menit";
    }

    protected static function formatJamKerjaLabel(?string $jamMasuk, ?string $jamKeluar): string
    {
        if (blank($jamMasuk) || blank($jamKeluar)) {
            return '-';
        }

        try {
            $masuk = Carbon::createFromFormat('H:i:s', $jamMasuk);
            $keluar = Carbon::createFromFormat('H:i:s', $jamKeluar);
        } catch (\Exception) {
            return '-';
        }

        if ($keluar->lt($masuk)) {
            $keluar->addDay();
        }

        $totalMinutes = $masuk->diffInMinutes($keluar);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours === 0 && $minutes === 0) {
            return '0 menit';
        }

        if ($minutes === 0) {
            return "{$hours} jam";
        }

        if ($hours === 0) {
            return "{$minutes} menit";
        }

        return "{$hours} jam {$minutes} menit";
    }


    protected static function monthOptions(): array
    {
        $now = Carbon::now();

        return collect(range(1, 12))
            ->mapWithKeys(function (int $month) use ($now): array {
                $date = $now->copy()->month($month);

                return [
                    $date->format('Y-m') => $date->locale('id')->translatedFormat('F Y'),
                ];
            })
            ->toArray();
    }

    protected static function isDetailTabActive(?HasTable $livewire = null): bool
    {
        return data_get($livewire, 'activeTab') === 'rincian';
    }
}
