<?php

namespace App\Filament\Resources;

use App\Enums\KategoriAkun;
use App\Filament\Resources\Akunting\InputTransaksiTokoResource;
use App\Filament\Resources\LaporanLabaRugiResource\Pages;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use App\Models\LaporanLabaRugi;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LaporanLabaRugiResource extends Resource
{
    protected static ?string $model = LaporanLabaRugi::class;
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Laba Rugi';
    protected static ?string $pluralLabel = 'Laba Rugi';
    protected static ?string $navigationIcon = 'hugeicons-pie-chart';

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
                    ->formatStateUsing(fn(?string $state) => self::formatMonthLabel($state)),
                TextColumn::make('total_penjualan')
                    ->label('Pendapatan')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('total_hpp')
                    ->label('Beban Pokok Penjualan')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('total_beban')
                    ->label('Beban Usaha')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('laba_rugi')
                    ->label('Laba')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
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
                            ->formatStateUsing(fn(?string $state) => self::formatMonthLabel($state)),
                        TextEntry::make('total_penjualan')
                            ->label('Pendapatan')
                            ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                        TextEntry::make('total_hpp')
                            ->label('Beban Pokok Penjualan')
                            ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                        TextEntry::make('laba_kotor')
                            ->label('Laba Kotor')
                            ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                        TextEntry::make('total_beban')
                            ->label('Beban Usaha')
                            ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                        TextEntry::make('laba_rugi')
                            ->label('Laba Bersih')
                            ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                    ])
                    ->columns(2),
                Section::make('Daftar Penjualan')
                    ->schema([
                        ViewEntry::make('penjualan_table')
                            ->label('')
                            ->view('filament.infolists.laba-rugi-penjualan-tab'),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
                Section::make('Daftar Beban')
                    ->schema([
                        ViewEntry::make('beban_table')
                            ->label('')
                            ->view('filament.infolists.laba-rugi-beban-tab'),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
                Section::make('Daftar Pembelian')
                    ->schema([
                        ViewEntry::make('pembelian_table')
                            ->label('')
                            ->view('filament.infolists.laba-rugi-pembelian-tab'),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
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
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();
        $penjualanTable = (new Penjualan())->getTable();
        $penjualanItemsTable = (new PenjualanItem())->getTable();
        $penjualanJasaTable = (new PenjualanJasa())->getTable();
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
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->whereRaw(
                "LOWER(COALESCE({$transaksiTable}.kategori_transaksi, {$kodeAkunTable}.kategori_akun)) = ?",
                [KategoriAkun::Beban->value]
            )
            ->groupBy('month_start', 'month_key');

        $penjualanSub = Penjualan::query()
            ->selectRaw("DATE_FORMAT({$penjualanTable}.tanggal_penjualan, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$penjualanTable}.tanggal_penjualan, '%Y-%m') as month_key")
            ->selectRaw("SUM({$penjualanItemsTable}.harga_jual * {$penjualanItemsTable}.qty) as total_penjualan")
            ->join($penjualanItemsTable, "{$penjualanItemsTable}.id_penjualan", '=', "{$penjualanTable}.id_penjualan")
            ->groupBy('month_start', 'month_key');

        $penjualanJasaSub = Penjualan::query()
            ->selectRaw("DATE_FORMAT({$penjualanTable}.tanggal_penjualan, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$penjualanTable}.tanggal_penjualan, '%Y-%m') as month_key")
            ->selectRaw("SUM({$penjualanJasaTable}.harga * IFNULL(NULLIF({$penjualanJasaTable}.qty, 0), 1)) as total_penjualan_jasa")
            ->join($penjualanJasaTable, "{$penjualanJasaTable}.id_penjualan", '=', "{$penjualanTable}.id_penjualan")
            ->groupBy('month_start', 'month_key');

        $monthsSub = DB::query()->fromSub($hppSub, 'hpp')
            ->select('month_start', 'month_key')
            ->union(
                DB::query()->fromSub($bebanSub, 'beban')->select('month_start', 'month_key')
            )
            ->union(
                DB::query()->fromSub($penjualanSub, 'penjualan')->select('month_start', 'month_key')
            )
            ->union(
                DB::query()->fromSub($penjualanJasaSub, 'penjualan_jasa')->select('month_start', 'month_key')
            );

        return LaporanLabaRugi::query()
            ->fromSub($monthsSub, $reportTable)
            ->leftJoinSub($hppSub, 'hpp', 'hpp.month_key', '=', "{$reportTable}.month_key")
            ->leftJoinSub($bebanSub, 'beban', 'beban.month_key', '=', "{$reportTable}.month_key")
            ->leftJoinSub($penjualanSub, 'penjualan', 'penjualan.month_key', '=', "{$reportTable}.month_key")
            ->leftJoinSub($penjualanJasaSub, 'penjualan_jasa', 'penjualan_jasa.month_key', '=', "{$reportTable}.month_key")
            ->select([
                "{$reportTable}.month_key",
                "{$reportTable}.month_start",
                DB::raw('COALESCE(hpp.total_hpp, 0) as total_hpp'),
                DB::raw('COALESCE(beban.total_beban, 0) as total_beban'),
                DB::raw('(COALESCE(penjualan.total_penjualan, 0) + COALESCE(penjualan_jasa.total_penjualan_jasa, 0)) as total_penjualan'),
                DB::raw('((COALESCE(penjualan.total_penjualan, 0) + COALESCE(penjualan_jasa.total_penjualan_jasa, 0)) - COALESCE(hpp.total_hpp, 0)) as laba_kotor'),
                DB::raw('((COALESCE(penjualan.total_penjualan, 0) + COALESCE(penjualan_jasa.total_penjualan_jasa, 0)) - (COALESCE(hpp.total_hpp, 0) + COALESCE(beban.total_beban, 0))) as laba_rugi'),
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

    public static function formatMonthLabel(?string $monthStart): string
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
        $penjualanTable = (new Penjualan())->getTable();

        $pembelianYears = Pembelian::query()
            ->selectRaw("YEAR({$pembelianTable}.tanggal) as tahun")
            ->distinct()
            ->pluck('tahun');

        $transaksiYears = InputTransaksiToko::query()
            ->selectRaw("YEAR({$transaksiTable}.tanggal_transaksi) as tahun")
            ->distinct()
            ->pluck('tahun');

        $penjualanYears = Penjualan::query()
            ->selectRaw("YEAR({$penjualanTable}.tanggal_penjualan) as tahun")
            ->distinct()
            ->pluck('tahun');

        $years = $pembelianYears
            ->merge($transaksiYears)
            ->merge($penjualanYears)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return $years->mapWithKeys(fn($year) => [(string) $year => (string) $year])->all();
    }
}
