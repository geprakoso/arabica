<?php

namespace App\Filament\Resources;

use Akaunting\Money\Money;
use App\Filament\Resources\PembelianReportResource\Pages;
use App\Models\Pembelian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PembelianReportResource extends BaseResource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Laporan Pembelian';

    protected static ?string $pluralLabel = 'Laporan Pembelian';

    protected static ?string $modelLabel = 'Laporan Pembelian';

    protected static ?string $pluralModelLabel = 'Laporan Pembelian';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['supplier', 'karyawan', 'items'])) // Eager load relasi yang dibutuhkan
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable(),
                TextColumn::make('total_items')
                    ->label('Total Qty')
                    ->badge()
                    ->color('info')
                    ->state(fn (Pembelian $record) => $record->items->sum('qty')), // menghitung total qty dari relasi items
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn (Pembelian $record) => self::formatCurrency(
                        $record->items->sum(fn ($item) => (int) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))
                    )) // menghitung total HPP dari relasi items
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_harga_jual')
                    ->label('Total Pembelian')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (Pembelian $record) => self::formatCurrency(
                        $record->items->sum(fn ($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0))
                    )), // menghitung total harga jual dari relasi items
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
                        Forms\Components\Select::make('period_type')
                            ->label('Tipe')
                            ->options([
                                'monthly' => 'Bulanan',
                                'quarterly' => '3 Bulan',
                            ])
                            ->reactive()
                            ->required(),
                        Forms\Components\Select::make('month')
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
                            ->visible(fn (Forms\Get $get): bool => $get('period_type') === 'monthly')
                            ->required(fn (Forms\Get $get): bool => $get('period_type') === 'monthly'),
                        Forms\Components\Select::make('quarter')
                            ->label('Quarter')
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Okt - Des)',
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('period_type') === 'quarterly')
                            ->required(fn (Forms\Get $get): bool => $get('period_type') === 'quarterly'),
                        Forms\Components\Select::make('year')
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

                            return $query->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);
                        }

                        if ($type === 'quarterly') {
                            $quarter = (int) ($data['quarter'] ?? 0);
                            if ($quarter < 1 || $quarter > 4) {
                                return $query;
                            }

                            $startMonth = (($quarter - 1) * 3) + 1;
                            $start = Carbon::create($year, $startMonth, 1)->startOfMonth();
                            $end = Carbon::create($year, $startMonth, 1)->addMonths(2)->endOfMonth();

                            return $query->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                \AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction::make('export')
                    ->label('Download')
                    ->filename('Laporan Pembelian'.'_'.date('d M Y'))
                    ->defaultFormat('pdf')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->modalHeading(false)
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Pembelian',
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

    // public static function canViewAny(): bool
    // {
    //     return Auth::user()->can('view Laporan Pembelian');
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelianReports::route('/'),
        ];
    }

    protected static function formatCurrency(int $value): string
    {

        return Money::IDR($value * 100)->formatWithoutZeroes();
    }
}
