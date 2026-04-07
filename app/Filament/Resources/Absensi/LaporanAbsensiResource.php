<?php

namespace App\Filament\Resources\Absensi;

use App\Filament\Resources\Absensi\LaporanAbsensiResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Models\Absensi;
use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanAbsensiResource extends BaseResource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Absensi';

    protected static ?string $modelLabel = 'Laporan Absensi';

    protected static ?string $pluralModelLabel = 'Laporan Absensi';

    protected static ?int $navigationSort = 5;

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
                    ->selectRaw("SUM(CASE WHEN jam_masuk IS NULL OR jam_keluar IS NULL THEN 0 WHEN jam_keluar >= jam_masuk THEN TIMESTAMPDIFF(MINUTE, jam_masuk, jam_keluar) ELSE TIMESTAMPDIFF(MINUTE, jam_masuk, ADDTIME(jam_keluar, '24:00:00')) END) as total_jam_kerja_menit")
                    ->selectRaw('COUNT(*) as total_absen')
                    ->groupBy('user_id')
                    ->with('user');

                if (! $hasManualSort) {
                    $query->orderByDesc('total_absen');
                }
            })
            ->recordAction(fn (HasTable $livewire): ?string => self::isDetailTabActive($livewire) ? 'detail' : null)
            ->recordUrl(null)
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->weight('bold')
                    ->description(fn (Model $record) => $record->user->email ?? '-')
                    ->icon('heroicon-m-user')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_hadir')
                    ->label('Hadir')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_izin')
                    ->label('Izin')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-document-text')
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_sakit')
                    ->label('Sakit')
                    ->numeric()
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-m-face-frown')
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_absen')
                    ->label('Total Absen')
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-calculator')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('total_jam_kerja_menit')
                    ->label('Durasi Kerja')
                    ->icon('heroicon-m-clock')
                    ->sortable()
                    ->state(fn (Model $record): string => self::formatTotalMinutesLabel((int) ($record->total_jam_kerja_menit ?? 0)))
                    ->visible(fn (HasTable $livewire): bool => ! self::isDetailTabActive($livewire)),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn (Model $record) => $record->tanggal->locale('id')->translatedFormat('l'))
                    ->icon('heroicon-m-calendar')
                    ->sortable()
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                BadgeableColumn::make('jam_masuk')
                    ->label('Jam Kehadiran')
                    ->iconColor('success')
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->formatStateUsing(fn (?string $state): string => self::getJamMasukMeta($state)['display'])
                    ->color(fn (?string $state): string => self::getJamMasukMeta($state)['color'] ?? 'white')
                    ->icon('heroicon-m-arrow-right-end-on-rectangle')
                    ->suffixBadges(fn (Model $record): array => self::getTelatBadges($record->jam_masuk)),
                TextColumn::make('jam_keluar')
                    ->label('Jam Keluar')
                    ->iconColor('danger')
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->date('H:i')
                    ->icon('heroicon-m-arrow-left-start-on-rectangle')
                    ->formatStateUsing(fn (?string $state): string => $state ? Carbon::createFromFormat('H:i:s', $state)->format('H:i') : '-')
                    ->placeholder('-'),
                TextColumn::make('jam_kerja')
                    ->label('Durasi Kerja')
                    ->icon('heroicon-m-clock')
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                    ->state(fn (Model $record): string => self::formatJamKerjaLabel($record->jam_masuk, $record->jam_keluar)),
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
                    ->icon(fn (string $state): string => match ($state) {
                        'hadir' => 'heroicon-m-check-circle',
                        'izin' => 'heroicon-m-document-text',
                        'sakit' => 'heroicon-m-plus-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
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
            ->actions([
                ActionGroup::make([
                    Action::make('detail')
                        ->label('Detail Lengkap')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Detail Absensi')
                        ->modalWidth('5xl')
                        ->modalSubmitAction(false)
                        ->slideOver()
                        ->infolist(fn (Infolist $infolist) => static::infolist($infolist))
                        ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire)),
                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->modalHeading('Edit Absensi')
                        ->modalDescription('Fitur ini memungkinkan perubahan data absensi secara manual. Pastikan data yang dimasukkan valid.')
                        ->form([
                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Absen')
                                ->required()
                                ->native(false)
                                ->displayFormat('d M Y'),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TimePicker::make('jam_masuk')
                                    ->label('Jam Masuk')
                                    ->required()
                                    ->default(now()->format('H:i'))
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('jam_keluar')
                                    ->label('Jam Pulang')
                                    ->default(now()->format('H:i'))
                                    ->seconds(false),
                            ]),
                            Forms\Components\Textarea::make('keterangan')
                                ->label('Alasan Perubahan')
                                ->helperText('Wajib diisi jika mengubah data jam/tanggal.')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->visible(fn (HasTable $livewire): bool => self::isDetailTabActive($livewire))
                        ->requiresConfirmation()
                        ->modalSubmitActionLabel('Simpan'),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Menu Aksi'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make()
                    ->schema([
                        Split::make([
                            Group::make([
                                ImageEntry::make('camera_test')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->width('100%')
                                    ->height('auto')
                                    ->extraImgAttributes([
                                        'class' => 'rounded-lg shadow-md object-cover w-full aspect-[4/3]',
                                        'alt' => 'Foto Absensi',
                                    ])
                                    ->columnSpanFull()
                                    ->defaultImageUrl(url('/images/placeholder-image.jpg'))
                                    ->visible(fn ($record) => $record->camera_test !== null),

                                TextEntry::make('status')
                                    ->label('Status Kehadiran')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn (string $state): string => match ($state) {
                                        'hadir' => 'success',
                                        'izin' => 'warning',
                                        'sakit' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn (string $state): string => match ($state) {
                                        'hadir' => 'heroicon-m-check-circle',
                                        'izin' => 'heroicon-m-document-text',
                                        'sakit' => 'heroicon-m-plus-circle',
                                        default => 'heroicon-m-question-mark-circle',
                                    })
                                    ->alignCenter(),
                            ])->grow(false),

                            Group::make([
                                InfolistSection::make('Informasi Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Nama')
                                            ->weight('bold')
                                            ->icon('heroicon-m-user-circle'),
                                        TextEntry::make('user.email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->color('gray'),
                                    ])
                                    ->columns(2),

                                InfolistSection::make('Waktu & Lokasi')
                                    ->icon('heroicon-m-clock')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('tanggal')
                                            ->date('l, d M Y')
                                            ->icon('heroicon-m-calendar'),
                                        TextEntry::make('jam_masuk')
                                            ->time('H:i')
                                            ->icon('heroicon-m-arrow-right-end-on-rectangle')
                                            ->color('success'),
                                        TextEntry::make('jam_keluar')
                                            ->time('H:i')
                                            ->placeholder('Belum Pulang')
                                            ->icon('heroicon-m-arrow-left-start-on-rectangle')
                                            ->color('danger'),
                                        TextEntry::make('lat_absen')
                                            ->label('Latitude')
                                            ->icon('heroicon-m-map-pin')
                                            ->visible(fn ($record) => $record->lat_absen),
                                        TextEntry::make('long_absen')
                                            ->label('Longitude')
                                            ->icon('heroicon-m-map-pin')
                                            ->visible(fn ($record) => $record->long_absen),
                                    ])
                                    ->columns(2),

                                InfolistSection::make('Keterangan')
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('keterangan')
                                            ->hiddenLabel()
                                            ->markdown()
                                            ->placeholder('Tidak ada keterangan tambahan.'),
                                    ]),
                            ])->grow(true),
                        ])->from('md'),
                    ]),
            ]);
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

    protected static function formatTotalMinutesLabel(int $totalMinutes): string
    {
        if ($totalMinutes <= 0) {
            return '0 menit';
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

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
