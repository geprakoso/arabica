<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Filament\Resources\PenjualanReportResource\Pages;
use App\Models\Penjualan;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

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
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['items', 'jasaItems', 'member', 'karyawan'])) // eager loading data relasi
            ->defaultSort('created_at', 'desc') // default sort
            ->recordAction('detail')
            ->recordUrl(null)
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->url(fn (Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab()
                    ->tooltip('Klik untuk melihat detail')
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
                    ->state(fn(Penjualan $record) => $record->items->sum('qty')) // menghitung total qty dari relasi items
                    ->sortable(),
                TextColumn::make('total_jasa')
                    ->label('Total Jasa')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        self::calculateServiceTotal($record)
                    ))
                    ->color('warning')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        self::calculateProductTotal($record) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => self::summarizeTotalPenjualan($query))
                            ->formatStateUsing(fn ($state) => self::formatCurrency((int) $state)),
                    ])
                    ->sortable(),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        self::calculateHppTotal($record)
                    )) // format currency
                    ->color('danger')
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => self::summarizeTotalHpp($query))
                            ->formatStateUsing(fn ($state) => self::formatCurrency((int) $state)),
                    ])
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('total_margin')
                    ->label('Margin')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        (self::calculateProductTotal($record) - self::calculateHppTotal($record)) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->color('success')
                    ->weight('bold')
                    ->summarize([
                        Summarizer::make()
                            ->label('Total')
                            ->using(fn ($query) => self::summarizeTotalMargin($query))
                            ->formatStateUsing(fn ($state) => self::formatCurrency((int) $state)),
                    ])
                    ->sortable(),
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
                            ->visible(fn(Get $get): bool => $get('period_type') === 'monthly')
                            ->required(fn(Get $get): bool => $get('period_type') === 'monthly'),
                        \Filament\Forms\Components\Select::make('quarter')
                            ->label('Quarter')
                            ->options([
                                1 => 'Q1 (Jan - Mar)',
                                2 => 'Q2 (Apr - Jun)',
                                3 => 'Q3 (Jul - Sep)',
                                4 => 'Q4 (Okt - Des)',
                            ])
                            ->visible(fn(Get $get): bool => $get('period_type') === 'quarterly')
                            ->required(fn(Get $get): bool => $get('period_type') === 'quarterly'),
                        \Filament\Forms\Components\Select::make('year')
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
                    ->filename('Laporan Penjualan' . '_' . date('d M Y'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->modalHeading(false)
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Penjualan',
                        'tanggal' => now()->format('d-m-Y'),
                    ]),
            ])
            ->actions([
                Action::make('detail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detail Laporan Penjualan')
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->slideOver()
                    ->infolist(fn (Infolist $infolist) => static::infolist($infolist)),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Detail Penjualan')
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('no_nota')
                                    ->label('No. Nota')
                                    ->weight('bold')
                                    ->color('primary')
                                    ->tooltip('Klik untuk melihat detail')
                                    ->icon('heroicon-m-document-text')
                                    ->url(fn (Penjualan $record) => PenjualanResource::getUrl('view', ['record' => $record]))
                                    ->openUrlInNewTab(),
                                TextEntry::make('tanggal_penjualan')
                                    ->label('Tanggal Penjualan')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('member.nama_member')
                                    ->label('Member')
                                    ->icon('heroicon-m-user-group')
                                    ->placeholder('-'),
                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                                TextEntry::make('sumber_transaksi')
                                    ->label('Sumber Transaksi')
                                    ->badge()
                                    ->state(fn (Penjualan $record): string => strtoupper((string) ($record->sumber_transaksi ?? '-'))),
                            ]),
                            InfoGroup::make([
                                TextEntry::make('total_qty')
                                    ->label('Total Qty')
                                    ->state(fn (Penjualan $record): int => (int) $record->items->sum('qty'))
                                    ->icon('heroicon-m-clipboard-document-list'),
                                TextEntry::make('total_jasa')
                                    ->label('Total Jasa')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency(self::calculateServiceTotal($record)))
                                    ->icon('heroicon-m-wrench-screwdriver'),

                                TextEntry::make('total_margin')
                                    ->label('Margin')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency(
                                        (self::calculateProductTotal($record) - self::calculateHppTotal($record)) + self::calculateServiceTotal($record)
                                    ))
                                    ->icon('heroicon-m-sparkles')
                                    ->color('success'),
                            ]),
                        ])->from('md'),
                    ])
                    ->compact(),
                InfoSection::make('Pembayaran')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('subtotal_produk')
                                    ->label('Subtotal Produk')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency(self::calculateProductTotal($record)))
                                    ->icon('heroicon-m-cube'),
                                TextEntry::make('subtotal_jasa')
                                    ->label('Subtotal Jasa')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency(self::calculateServiceTotal($record)))
                                    ->icon('heroicon-m-wrench-screwdriver'),
                                TextEntry::make('diskon_total')
                                    ->label('Diskon')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency((int) ($record->diskon_total ?? 0)))
                                    ->icon('heroicon-m-ticket'),
                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency((int) ($record->grand_total ?? 0)))
                                    ->icon('heroicon-m-banknotes')
                                    ->color('success'),
                                TextEntry::make('total_bayar')
                                    ->label('Total Dibayar')
                                    ->state(fn (Penjualan $record): string => self::formatCurrency((int) ($record->pembayaran_sum_jumlah ?? 0)))
                                    ->icon('heroicon-m-wallet'),
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Bayar')
                                    ->state(function (Penjualan $record): string {
                                        $grandTotal = (int) ($record->grand_total ?? 0);
                                        $totalPaid = (int) ($record->pembayaran_sum_jumlah ?? 0);

                                        return self::formatCurrency(max(0, $grandTotal - $totalPaid));
                                    })
                                    ->icon('heroicon-m-clock'),
                                TextEntry::make('kelebihan_bayar')
                                    ->label('Kelebihan Bayar')
                                    ->state(function (Penjualan $record): string {
                                        $grandTotal = (int) ($record->grand_total ?? 0);
                                        $totalPaid = (int) ($record->pembayaran_sum_jumlah ?? 0);

                                        return self::formatCurrency(max(0, $totalPaid - $grandTotal));
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualanReports::route('/'),
        ];
    }

    // format currency
    protected static function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
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

    protected static function summarizeTotalPenjualan(QueryBuilder $query): int
    {
        $penjualan = new Penjualan();
        $salesTable = $penjualan->getTable();
        $salesKey = $penjualan->getKeyName();

        $itemsSub = DB::table('tb_penjualan_item')
            ->selectRaw('id_penjualan, COALESCE(SUM(qty * harga_jual), 0) as total_penjualan')
            ->groupBy('id_penjualan');

        $jasaSub = DB::table('tb_penjualan_jasa')
            ->selectRaw('id_penjualan, COALESCE(SUM(qty * harga), 0) as total_jasa')
            ->groupBy('id_penjualan');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;
        $summaryQuery->columns = null;
        $summaryQuery->columns = null;

        $summary = $summaryQuery
            ->leftJoinSub($itemsSub, 'items_sum', 'items_sum.id_penjualan', '=', "{$salesTable}.{$salesKey}")
            ->leftJoinSub($jasaSub, 'jasa_sum', 'jasa_sum.id_penjualan', '=', "{$salesTable}.{$salesKey}")
            ->selectRaw('COALESCE(SUM(COALESCE(items_sum.total_penjualan, 0) + COALESCE(jasa_sum.total_jasa, 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }

    protected static function summarizeTotalMargin(QueryBuilder $query): int
    {
        $penjualan = new Penjualan();
        $salesTable = $penjualan->getTable();
        $salesKey = $penjualan->getKeyName();

        $itemsSub = DB::table('tb_penjualan_item')
            ->selectRaw('id_penjualan, COALESCE(SUM(qty * harga_jual), 0) as total_penjualan, COALESCE(SUM(qty * hpp), 0) as total_hpp')
            ->groupBy('id_penjualan');

        $jasaSub = DB::table('tb_penjualan_jasa')
            ->selectRaw('id_penjualan, COALESCE(SUM(qty * harga), 0) as total_jasa')
            ->groupBy('id_penjualan');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;

        $summary = $summaryQuery
            ->leftJoinSub($itemsSub, 'items_sum', 'items_sum.id_penjualan', '=', "{$salesTable}.{$salesKey}")
            ->leftJoinSub($jasaSub, 'jasa_sum', 'jasa_sum.id_penjualan', '=', "{$salesTable}.{$salesKey}")
            ->selectRaw('COALESCE(SUM(COALESCE(items_sum.total_penjualan, 0) - COALESCE(items_sum.total_hpp, 0) + COALESCE(jasa_sum.total_jasa, 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }

    protected static function summarizeTotalHpp(QueryBuilder $query): int
    {
        $penjualan = new Penjualan();
        $salesTable = $penjualan->getTable();
        $salesKey = $penjualan->getKeyName();

        $itemsSub = DB::table('tb_penjualan_item')
            ->selectRaw('id_penjualan, COALESCE(SUM(qty * hpp), 0) as total_hpp')
            ->groupBy('id_penjualan');

        $summaryQuery = clone $query;
        $summaryQuery->orders = null;
        $summaryQuery->limit = null;
        $summaryQuery->offset = null;
        $summaryQuery->columns = null;

        $summary = $summaryQuery
            ->leftJoinSub($itemsSub, 'items_sum', 'items_sum.id_penjualan', '=', "{$salesTable}.{$salesKey}")
            ->selectRaw('COALESCE(SUM(COALESCE(items_sum.total_hpp, 0)), 0) as total')
            ->value('total');

        return (int) ($summary ?? 0);
    }
}
