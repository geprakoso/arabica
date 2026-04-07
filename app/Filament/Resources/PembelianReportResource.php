<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pembelian;
use Akaunting\Money\Money;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Filament\Infolists\Infolist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Indicator;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Split;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\PembelianResource;
use App\Filament\Actions\SummaryExportHeaderAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Infolists\Components\Group as InfoGroup;
use Illuminate\Database\Query\Builder as QueryBuilder;
use App\Filament\Resources\PembelianReportResource\Pages;
use Filament\Infolists\Components\Section as InfoSection;

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
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['supplier', 'karyawan', 'items'])) // Eager load relasi yang dibutuhkan
            ->defaultSort('created_at', 'desc')
            ->recordAction('detail')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->tooltip('Klik untuk melihat detail')
                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab()
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
                    ->formatStateUsing(fn($state) => Str::title($state))
                    ->icon('heroicon-m-building-storefront')
                    ->weight('medium')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable(),

                TextColumn::make('nota_supplier')
                    ->label('Nota Referensi')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-receipt-refund')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jenis_pembayaran')
                    ->label('Status ')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                    ->colors([
                        'success' => 'lunas',
                        'danger' => 'tempo',
                    ]),
                TextColumn::make('total_pembayaran')
                    ->label('Total Pembayaran')
                    ->state(fn(Pembelian $record) => self::formatCurrency($record->calculateTotalPembelian()))
                    ->color('success')
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => self::summarizeTotalPembelian($query))
                            ->formatStateUsing(fn($state) => self::formatCurrency((int) $state)),
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_dibayar')
                    ->label('Total Dibayar')
                    ->state(fn(Pembelian $record) => self::formatCurrency(
                        $record->pembayaran()->sum('jumlah') ?? 0
                    ))
                    ->color('warning')
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => self::summarizeTotalDibayar($query))
                            ->formatStateUsing(fn($state) => self::formatCurrency((int) $state)),
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sisa_bayar')
                    ->label('Sisa Pembayaran')
                    ->color('info')
                    ->state(function (Pembelian $record) {
                        $total = $record->calculateTotalPembelian();
                        $dibayar = (float) ($record->pembayaran()->sum('jumlah') ?? 0);
                        return self::formatCurrency(max(0, $total - $dibayar));
                    })
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn($query) => self::summarizeSisaPembelian($query))
                            ->formatStateUsing(fn($state) => self::formatCurrency((int) $state)),
                    ])
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('periodik')
                    ->label('Periodik')
                    ->default(fn(): array => [
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
                            ->visible(fn(Forms\Get $get): bool => $get('period_type') === 'monthly')
                            ->required(fn(Forms\Get $get): bool => $get('period_type') === 'monthly'),
                        Forms\Components\Select::make('quarter')
                            ->label('Quarter')
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Okt - Des)',
                            ])
                            ->visible(fn(Forms\Get $get): bool => $get('period_type') === 'quarterly')
                            ->required(fn(Forms\Get $get): bool => $get('period_type') === 'quarterly'),
                        Forms\Components\Select::make('year')
                            ->label('Tahun')
                            ->options(function (): array {
                                $year = now()->year;
                                return collect(range($year - 4, $year + 1))
                                    ->mapWithKeys(fn(int $value) => [$value => (string) $value])
                                    ->all();
                            })
                            ->default(fn(): int => now()->year)
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
                SummaryExportHeaderAction::make('export')
                    ->label('Download')
                    ->filename('Laporan Pembelian' . '_' . date('d M Y'))
                    ->defaultFormat('pdf')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->modalHeading(false)
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Pembelian',
                        'tanggal' => now()->format('d-m-Y'),
                    ])
                    ->summaryResolver(function (Builder $query, Collection $records): array {
                        $baseQuery = $query->toBase();
                        $totalPembelian = self::summarizeTotalPembelian($baseQuery);
                        $totalDibayar = self::summarizeTotalDibayar($baseQuery);
                        $totalSisa = self::summarizeSisaPembelian($baseQuery);

                        return [
                            [
                                'label' => 'Total Transaksi',
                                'value' => number_format($records->count(), 0, ',', '.'),
                            ],
                            [
                                'label' => 'Total Pembelian',
                                'value' => self::formatCurrency($totalPembelian),
                            ],
                            [
                                'label' => 'Total Dibayar',
                                'value' => self::formatCurrency($totalDibayar),
                            ],
                            [
                                'label' => 'Sisa Pembayaran',
                                'value' => self::formatCurrency($totalSisa),
                            ],
                        ];
                    }),
            ])
            ->actions([
                Action::make('detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Laporan Pembelian')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->slideOver()
                    ->infolist(fn(Infolist $infolist) => static::infolist($infolist)),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Detail Pembelian')
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('no_po')
                                    ->label('No. PO')
                                    ->weight('bold')
                                    ->icon('heroicon-m-document-text')
                                    ->color('primary')
                                    ->tooltip('Klik untuk melihat detail')
                                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
                                    ->openUrlInNewTab(),
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                                TextEntry::make('nota_supplier')
                                    ->label('Nota Referensi')
                                    ->placeholder('-')
                                    ->icon('heroicon-m-receipt-refund'),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('supplier.nama_supplier')
                                    ->label('Supplier')
                                    ->icon('heroicon-m-building-storefront')
                                    ->placeholder('-'),
                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                                TextEntry::make('jenis_pembayaran')
                                    ->label('Status Pembayaran')
                                    ->badge()
                                    ->formatStateUsing(fn(?string $state): ?string => $state ? strtoupper(str_replace('_', ' ', $state)) : null)
                                    ->colors([
                                        'success' => 'lunas',
                                        'danger' => 'tempo',
                                    ]),
                            ]),
                        ])->from('md'),
                    ])
                    ->compact(),
                InfoSection::make('Pembayaran')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_pembelian')
                                    ->label('Total Pembelian')
                                    ->state(fn(Pembelian $record): string => self::formatCurrency($record->calculateTotalPembelian()))
                                    ->icon('heroicon-m-banknotes')
                                    ->color('success'),
                                TextEntry::make('total_dibayar')
                                    ->label('Total Dibayar')
                                    ->state(fn(Pembelian $record): string => self::formatCurrency(
                                        (int) ($record->pembayaran()->sum('jumlah') ?? 0)
                                    ))
                                    ->icon('heroicon-m-wallet'),
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Pembayaran')
                                    ->state(function (Pembelian $record): string {
                                        $total = $record->calculateTotalPembelian();
                                        $dibayar = (int) ($record->pembayaran()->sum('jumlah') ?? 0);

                                        return self::formatCurrency(max(0, $total - $dibayar));
                                    })
                                    ->icon('heroicon-m-clock'),
                                TextEntry::make('kelebihan_bayar')
                                    ->label('Kelebihan Bayar')
                                    ->state(function (Pembelian $record): string {
                                        $total = $record->calculateTotalPembelian();
                                        $dibayar = (int) ($record->pembayaran()->sum('jumlah') ?? 0);

                                        return self::formatCurrency(max(0, $dibayar - $total));
                                    })
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->color('warning'),
                            ]),
                    ])
                    ->compact(),
            ]);
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

    protected static function summarizeTotalPembelian(QueryBuilder $query): int
    {
        $pembelian = new Pembelian();
        $purchaseTable = $pembelian->getTable();
        $purchaseKey = $pembelian->getKeyName();

        $itemsSub = DB::table('tb_pembelian_item')
            ->selectRaw('id_pembelian, COALESCE(SUM(qty * hpp), 0) as total_items')
            ->groupBy('id_pembelian');

        $jasaSub = DB::table('tb_pembelian_jasa')
            ->selectRaw('id_pembelian, COALESCE(SUM(qty * harga), 0) as total_jasa')
            ->groupBy('id_pembelian');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;
        $summaryQuery->columns = null;

        $summary = $summaryQuery
            ->leftJoinSub($itemsSub, 'items_sum', 'items_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->leftJoinSub($jasaSub, 'jasa_sum', 'jasa_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->selectRaw('COALESCE(SUM(COALESCE(items_sum.total_items, 0) + COALESCE(jasa_sum.total_jasa, 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }

    protected static function summarizeTotalDibayar(QueryBuilder $query): int
    {
        $pembelian = new Pembelian();
        $purchaseTable = $pembelian->getTable();
        $purchaseKey = $pembelian->getKeyName();

        $paySub = DB::table('tb_pembelian_pembayaran')
            ->selectRaw('id_pembelian, COALESCE(SUM(jumlah), 0) as total_paid')
            ->groupBy('id_pembelian');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;
        $summaryQuery->columns = null;

        $summary = $summaryQuery
            ->leftJoinSub($paySub, 'pay_sum', 'pay_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->selectRaw('COALESCE(SUM(COALESCE(pay_sum.total_paid, 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }

    protected static function summarizeSisaPembelian(QueryBuilder $query): int
    {
        $pembelian = new Pembelian();
        $purchaseTable = $pembelian->getTable();
        $purchaseKey = $pembelian->getKeyName();

        $itemsSub = DB::table('tb_pembelian_item')
            ->selectRaw('id_pembelian, COALESCE(SUM(qty * hpp), 0) as total_items')
            ->groupBy('id_pembelian');

        $jasaSub = DB::table('tb_pembelian_jasa')
            ->selectRaw('id_pembelian, COALESCE(SUM(qty * harga), 0) as total_jasa')
            ->groupBy('id_pembelian');

        $paySub = DB::table('tb_pembelian_pembayaran')
            ->selectRaw('id_pembelian, COALESCE(SUM(jumlah), 0) as total_paid')
            ->groupBy('id_pembelian');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;
        $summaryQuery->columns = null;

        $summary = $summaryQuery
            ->leftJoinSub($itemsSub, 'items_sum', 'items_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->leftJoinSub($jasaSub, 'jasa_sum', 'jasa_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->leftJoinSub($paySub, 'pay_sum', 'pay_sum.id_pembelian', '=', "{$purchaseTable}.{$purchaseKey}")
            ->selectRaw('COALESCE(SUM(GREATEST((COALESCE(items_sum.total_items, 0) + COALESCE(jasa_sum.total_jasa, 0)) - COALESCE(pay_sum.total_paid, 0), 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }
}
