<?php

namespace App\Filament\Resources;

use App\Enums\KategoriAkun;
use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use App\Filament\Resources\LaporanLabaRugiResource\Pages;
use App\Models\InputTransaksiToko;
use App\Models\LaporanLabaRugi;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LaporanLabaRugiResource extends Resource
{
    protected static ?string $model = LaporanLabaRugi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month_start')
                    ->label('Bulan')
                    ->formatStateUsing(fn (?string $state) => self::formatMonthLabel($state)),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->money('idr', true),
                TextColumn::make('total_beban')
                    ->label('Total Beban')
                    ->money('idr', true),
                TextColumn::make('laba_rugi')
                    ->label('Laba / Rugi')
                    ->money('idr', true),
            ])
            ->defaultSort('month_start', 'desc')
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(self::yearOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return $query->whereRaw('YEAR(laporan_laba_rugis.month_start) = ?', [$data['value']]);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ringkasan Laba Rugi')
                    ->schema([
                        TextEntry::make('month_start')
                            ->label('Bulan')
                            ->formatStateUsing(fn (?string $state) => self::formatMonthLabel($state)),
                        TextEntry::make('total_hpp')
                            ->label('Total HPP')
                            ->money('idr', true),
                        TextEntry::make('total_beban')
                            ->label('Total Beban')
                            ->money('idr', true),
                        TextEntry::make('laba_rugi')
                            ->label('Laba / Rugi')
                            ->money('idr', true),
                    ])
                    ->columns(2),
                Section::make('Detail Beban')
                    ->schema([
                        RepeatableEntry::make('beban_items')
                            ->label('')
                            ->state(fn ($record) => self::bebanDetailsForMonth($record->month_key))
                            ->schema([
                                TextEntry::make('tanggal_transaksi')
                                    ->label('Tanggal')
                                    ->state(fn (InputTransaksiToko $record) => $record->tanggal_transaksi)
                                    ->date('d M Y'),
                                TextEntry::make('jenisAkun.nama_jenis_akun')
                                    ->label('Jenis Akun')
                                    ->state(fn (InputTransaksiToko $record) => $record->jenisAkun?->nama_jenis_akun)
                                    ->placeholder('-'),
                                TextEntry::make('keterangan_transaksi')
                                    ->label('Keterangan')
                                    ->state(fn (InputTransaksiToko $record) => $record->keterangan_transaksi ?: '-')
                                    ->url(fn (InputTransaksiToko $record) => InputTransaksiTokoResource::getUrl('view', ['record' => $record]))
                                    ->openUrlInNewTab(),
                                TextEntry::make('nominal_transaksi')
                                    ->label('Nominal')
                                    ->state(fn (InputTransaksiToko $record) => $record->nominal_transaksi)
                                    ->money('idr', true),
                            ])
                            ->contained(false)
                            ->placeholder('Tidak ada data beban untuk bulan ini.')
                            ->columns(4),
                    ])
                    ->columnSpanFull(),
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
            'index' => Pages\ListLaporanLabaRugis::route('/'),
            'view' => Pages\ViewLaporanLabaRugi::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $pembelianTable = (new Pembelian())->getTable();
        $itemsTable = (new PembelianItem())->getTable();
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $reportTable = (new LaporanLabaRugi())->getTable();

        $hppSub = Pembelian::query()
            ->selectRaw("DATE_FORMAT({$pembelianTable}.tanggal, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$pembelianTable}.tanggal, '%Y-%m') as month_key")
            ->selectRaw("SUM({$itemsTable}.hpp * {$itemsTable}.qty) as total_hpp")
            ->join($itemsTable, "{$itemsTable}.id_pembelian", '=', "{$pembelianTable}.id_pembelian")
            ->groupBy('month_start', 'month_key');

        $bebanSub = InputTransaksiToko::query()
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m') as month_key")
            ->selectRaw("SUM({$transaksiTable}.nominal_transaksi) as total_beban")
            ->where("{$transaksiTable}.kategori_transaksi", KategoriAkun::Beban->value)
            ->groupBy('month_start', 'month_key');

        $monthsSub = DB::query()->fromSub($hppSub, 'hpp')
            ->select('month_start', 'month_key')
            ->union(
                DB::query()->fromSub($bebanSub, 'beban')->select('month_start', 'month_key')
            );

        return LaporanLabaRugi::query()
            ->fromSub($monthsSub, $reportTable)
            ->leftJoinSub($hppSub, 'hpp', 'hpp.month_key', '=', "{$reportTable}.month_key")
            ->leftJoinSub($bebanSub, 'beban', 'beban.month_key', '=', "{$reportTable}.month_key")
            ->select([
                "{$reportTable}.month_key",
                "{$reportTable}.month_start",
                DB::raw('COALESCE(hpp.total_hpp, 0) as total_hpp'),
                DB::raw('COALESCE(beban.total_beban, 0) as total_beban'),
                DB::raw('(COALESCE(hpp.total_hpp, 0) - COALESCE(beban.total_beban, 0)) as laba_rugi'),
            ])
            ->orderByDesc("{$reportTable}.month_start");
    }

    public static function resolveRecordRouteBinding(int | string $key): ?\Illuminate\Database\Eloquent\Model
    {
        $reportTable = (new LaporanLabaRugi())->getTable();

        return static::getEloquentQuery()
            ->where("{$reportTable}.month_key", $key)
            ->first();
    }

    protected static function formatMonthLabel(?string $monthStart): string
    {
        if (blank($monthStart)) {
            return '-';
        }

        $date = Carbon::parse($monthStart);
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

        $monthLabel = $months[$date->month] ?? $date->format('F');

        return $monthLabel;
    }

    /**
     * @return array<string, string>
     */
    protected static function yearOptions(): array
    {
        $pembelianTable = (new Pembelian())->getTable();
        $transaksiTable = (new InputTransaksiToko())->getTable();

        $pembelianYears = Pembelian::query()
            ->selectRaw("YEAR({$pembelianTable}.tanggal) as tahun")
            ->distinct()
            ->pluck('tahun');

        $transaksiYears = InputTransaksiToko::query()
            ->selectRaw("YEAR({$transaksiTable}.tanggal_transaksi) as tahun")
            ->distinct()
            ->pluck('tahun');

        $years = $pembelianYears
            ->merge($transaksiYears)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return $years->mapWithKeys(fn ($year) => [(string) $year => (string) $year])->all();
    }

    protected static function bebanDetailsForMonth(?string $monthKey): Collection
    {
        if (blank($monthKey)) {
            return collect();
        }

        $date = Carbon::createFromFormat('Y-m', $monthKey);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        return InputTransaksiToko::query()
            ->with('jenisAkun')
            ->where('kategori_transaksi', KategoriAkun::Beban->value)
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->orderBy('tanggal_transaksi')
            ->get();
    }
}
