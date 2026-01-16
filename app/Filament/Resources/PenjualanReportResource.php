<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Filament\Resources\PenjualanReportResource\Pages;
use App\Models\Penjualan;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class PenjualanReportResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $pluralLabel = 'Laporan Penjualan';

    protected static ?string $modelLabel = 'Laporan Penjualan';

    protected static ?string $pluralModelLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items', 'jasaItems', 'member', 'karyawan'])) // eager loading data relasi
            ->defaultSort('tanggal_penjualan', 'desc') // default sort
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->icon('heroicon-m-user-group')
                    ->weight('medium')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable(),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->badge()
                    ->color('info')
                    ->state(fn (Penjualan $record) => $record->items->sum('qty')) // menghitung total qty dari relasi items
                    ->sortable(),
                TextColumn::make('total_jasa')
                    ->label('Total Jasa')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateServiceTotal($record)
                    ))
                    ->color('warning')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateProductTotal($record) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->sortable(),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateHppTotal($record)
                    )) // format currency
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('total_margin')
                    ->label('Margin')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        (self::calculateProductTotal($record) - self::calculateHppTotal($record)) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('periodik')
                    ->label('Periodik')
                    ->default(fn (): array => [
                        'isActive' => true,
                        'period_type' => 'monthly',
                        'month' => now()->month,
                        'year' => now()->year,
                    ])
                    ->form([
                        \Filament\Forms\Components\Select::make('period_type')
                            ->label('Tipe')
                            ->options([
                                'monthly' => 'Bulanan',
                                'quarterly' => '3 Bulan',
                            ])
                            ->reactive()
                            ->required(),
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ])
                            ->visible(fn (Get $get): bool => $get('period_type') === 'monthly')
                            ->required(fn (Get $get): bool => $get('period_type') === 'monthly'),
                        \Filament\Forms\Components\Select::make('quarter')
                            ->label('Quarter')
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Okt - Des)',
                            ])
                            ->visible(fn (Get $get): bool => $get('period_type') === 'quarterly')
                            ->required(fn (Get $get): bool => $get('period_type') === 'quarterly'),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function (): array {
                                $year = now()->year;
                                return collect(range($year - 4, $year + 1))
                                    ->mapWithKeys(fn (int $value) => [$value => (string) $value])
                                    ->all();
                            })
                            ->default(fn (): int => now()->year)
                            ->required(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $type = $data['period_type'] ?? null;
                        $year = $data['year'] ?? null;

                        if (! $type || ! $year) {
                            return [];
                        }

                        if ($type === 'monthly') {
                            $month = (int) ($data['month'] ?? 0);
                            $months = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember',
                            ];

                            $monthLabel = $months[$month] ?? 'Bulan';
                            $now = now();
                            $isThisMonth = $month === (int) $now->month && (int) $year === (int) $now->year;
                            $label = $monthLabel . ' ' . $year;
                            $text = $isThisMonth ? ('Bulan ini (' . $label . ')') : $label;

                            return [Indicator::make('Periodik: ' . $text)];
                        }

                        if ($type === 'quarterly') {
                            $quarter = (int) ($data['quarter'] ?? 0);
                            $quarterLabel = $quarter >= 1 && $quarter <= 4 ? 'Q' . $quarter : 'Quarter';

                            return [Indicator::make('Periodik: ' . $quarterLabel . ' ' . $year)];
                        }

                        return [];
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['period_type'] ?? null;
                        $year = (int) ($data['year'] ?? 0);

                        if (! $type || $year < 1) {
                            return $query;
                        }

                        if ($type === 'monthly') {
                            $month = (int) ($data['month'] ?? 0);
                            if ($month < 1 || $month > 12) {
                                return $query;
                            }

                            $start = Carbon::create($year, $month, 1)->startOfMonth();
                            $end = Carbon::create($year, $month, 1)->endOfMonth();

                            return $query->whereBetween('tanggal_penjualan', [$start->toDateString(), $end->toDateString()]);
                        }

                        if ($type === 'quarterly') {
                            $quarter = (int) ($data['quarter'] ?? 0);
                            if ($quarter < 1 || $quarter > 4) {
                                return $query;
                            }

                            $startMonth = (($quarter - 1) * 3) + 1;
                            $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                            $end = Carbon::create($year, $startMonth, 1)->addMonths(2)->endOfMonth();

                            return $query->whereBetween('tanggal_penjualan', [$start->toDateString(), $end->toDateString()]);
                        }

                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('sumber_transaksi')
                    ->label('Sumber Transaksi')
                    ->options([
                        'pos' => 'POS',
                        'manual' => 'Manual',
                    ])
                    ->native(false)
                    ->placeholder('Semua'),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Download')
                    ->defaultFormat('pdf')
                    ->filename('Laporan Penjualan'.'_'.date('d M Y'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->modalHeading(false)
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Penjualan',
                        'tanggal' => now()->format('d-m-Y'),
                    ]),
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
            'index' => Pages\ListPenjualanReports::route('/'),
        ];
    }

    // format currency
    protected static function formatCurrency(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    protected static function calculateProductTotal(Penjualan $record): int
    {
        return (int) $record->items->sum(function ($item): int {
            $qty = (int) ($item->qty ?? 0);
            $harga = (int) ($item->harga_jual ?? 0);

            return $harga * $qty;
        });
    }

    protected static function calculateHppTotal(Penjualan $record): int
    {
        return (int) $record->items->sum(function ($item): int {
            $qty = (int) ($item->qty ?? 0);
            $hpp = (int) ($item->hpp ?? 0);

            return $hpp * $qty;
        });
    }

    protected static function calculateServiceTotal(Penjualan $record): int
    {
        return (int) $record->jasaItems->sum(function ($service): int {
            $qty = max(1, (int) ($service->qty ?? 1));
            $harga = (int) ($service->harga ?? 0);

            return $harga * $qty;
        });
    }
}
