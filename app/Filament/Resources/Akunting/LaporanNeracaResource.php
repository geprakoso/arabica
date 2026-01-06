<?php

namespace App\Filament\Resources\Akunting;

use App\Enums\KategoriAkun;
use App\Enums\KelompokNeraca;
use App\Filament\Resources\Akunting\LaporanNeracaResource\Pages;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use App\Models\LaporanNeraca;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LaporanNeracaResource extends BaseResource
{
    protected static ?string $model = LaporanNeraca::class;
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Neraca';
    protected static ?string $pluralLabel = 'Neraca';
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $slug = 'laporan-neraca';
    protected static array $kelompokNeracaMapping = [
        // Mapping manual: 'kode_akun' => KelompokNeraca::...
        // Contoh:
        // '101' => KelompokNeraca::AsetLancar,
        // '201' => KelompokNeraca::LiabilitasJangkaPendek,
        '11' => KelompokNeraca::AsetLancar,
        '12' => KelompokNeraca::AsetTidakLancar,
        '21' => KelompokNeraca::LiabilitasJangkaPendek,
        '22' => KelompokNeraca::LiabilitasJangkaPanjang,
        
    ];

    // public static function shouldRegisterNavigation(): bool
    // {
    //     // Hide this resource from the navigation; keep routes accessible.
    //     return false;
    // }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('month_start')
                    ->label('Periode')
                    ->formatStateUsing(fn (?string $state) => self::formatMonthLabel($state)),
                TextColumn::make('total_aset')
                    ->label('Total Aset')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('total_kewajiban')
                    ->label('Total Kewajiban')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('total_ekuitas')
                    ->label('Total Ekuitas')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->getStateUsing(function ($record): float {
                        $aset = (float) ($record->total_aset ?? 0);
                        $kewajiban = (float) ($record->total_kewajiban ?? 0);
                        $ekuitas = (float) ($record->total_ekuitas ?? 0);

                        return $aset - ($kewajiban + $ekuitas);
                    })
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                    ->color(fn ($state) => (float) $state === 0.0 ? 'success' : 'danger'),
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

                        $reportTable = (new LaporanNeraca())->getTable();

                        return $query->whereRaw("YEAR({$reportTable}.month_start) = ?", [$data['value']]);
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
            ->columns(1)
            ->schema([
                Section::make('Laporan Neraca')
                    ->maxWidth('max-w-none w-full')
                    ->schema([
                        ViewEntry::make('neraca_table')
                            ->label('')
                            ->view('filament.infolists.neraca-table')
                            ->state(fn ($record) => self::neracaViewData($record))
                            ->maxWidth('max-w-none w-full')
                            ->extraEntryWrapperAttributes(['class' => 'w-full max-w-none'])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanNeracas::route('/'),
            'view' => Pages\ViewLaporanNeraca::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $reportTable = (new LaporanNeraca())->getTable();

        $monthsSub = InputTransaksiToko::query()
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m') as month_key")
            ->groupBy('month_start', 'month_key');

        return LaporanNeraca::query()
            ->fromSub($monthsSub, $reportTable)
            ->select([
                "{$reportTable}.month_key",
                "{$reportTable}.month_start",
            ])
            ->selectSub(self::totalAsetSub($reportTable), 'total_aset')
            ->selectSub(self::totalKewajibanSub($reportTable), 'total_kewajiban')
            ->selectSub(self::totalEkuitasSub($reportTable), 'total_ekuitas')
            ->orderByDesc("{$reportTable}.month_start");
    }

    protected static function totalAsetSub(string $reportTable): Builder
    {
        return self::totalByKelompokSub(
            $reportTable,
            KategoriAkun::Aktiva,
            [
                KelompokNeraca::AsetLancar,
                KelompokNeraca::AsetTidakLancar,
            ],
        );
    }

    protected static function totalKewajibanSub(string $reportTable): Builder
    {
        return self::totalByKelompokSub(
            $reportTable,
            KategoriAkun::Pasiva,
            [
                KelompokNeraca::LiabilitasJangkaPendek,
                KelompokNeraca::LiabilitasJangkaPanjang,
            ],
        );
    }

    protected static function totalEkuitasSub(string $reportTable): Builder
    {
        return self::totalByKelompokSub(
            $reportTable,
            KategoriAkun::Pasiva,
            [
                KelompokNeraca::Ekuitas,
            ],
        );
    }

    protected static function totalByKelompokSub(
        string $reportTable,
        KategoriAkun $kategori,
        array $kelompokList,
    ): Builder {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();

        $kodeAkunList = self::getKodeAkunByKelompok($kelompokList);

        return InputTransaksiToko::query()
            ->selectRaw("COALESCE(SUM({$transaksiTable}.nominal_transaksi), 0)")
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->where("{$transaksiTable}.kategori_transaksi", $kategori->value)
            ->when(
                empty($kodeAkunList),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->whereIn("{$kodeAkunTable}.kode_akun", $kodeAkunList),
            )
            ->whereRaw("{$transaksiTable}.tanggal_transaksi <= LAST_DAY({$reportTable}.month_start)");
    }

    public static function neracaViewData($record): array
    {
        $monthStart = $record?->month_start;
        $asOf = $monthStart ? Carbon::parse($monthStart)->endOfMonth() : now()->endOfMonth();

        $asetLancar = self::fetchKelompokRows($asOf, KategoriAkun::Aktiva, KelompokNeraca::AsetLancar);
        $asetTidakLancar = self::fetchKelompokRows($asOf, KategoriAkun::Aktiva, KelompokNeraca::AsetTidakLancar);
        $liabilitasPendek = self::fetchKelompokRows($asOf, KategoriAkun::Pasiva, KelompokNeraca::LiabilitasJangkaPendek);
        $liabilitasPanjang = self::fetchKelompokRows($asOf, KategoriAkun::Pasiva, KelompokNeraca::LiabilitasJangkaPanjang);
        $ekuitas = self::fetchKelompokRows($asOf, KategoriAkun::Pasiva, KelompokNeraca::Ekuitas);

        $persediaanBarang = [
            'nama' => 'Persediaan Barang',
            'total' => self::totalInventoryHpp($asOf),
        ];

        array_unshift($asetLancar, $persediaanBarang);

        $totalAsetLancar = self::sumRows($asetLancar);
        $totalAsetTidakLancar = self::sumRows($asetTidakLancar);
        $totalLiabilitasPendek = self::sumRows($liabilitasPendek);
        $totalLiabilitasPanjang = self::sumRows($liabilitasPanjang);
        $totalEkuitas = self::sumRows($ekuitas);

        $totalAset = $totalAsetLancar + $totalAsetTidakLancar;
        $totalLiabilitas = $totalLiabilitasPendek + $totalLiabilitasPanjang;
        $totalLiabilitasEkuitas = $totalLiabilitas + $totalEkuitas;

        return [
            'company_name' => config('app.name'),
            'as_of_label' => self::formatTanggal($asOf),
            'aset_lancar' => $asetLancar,
            'aset_tidak_lancar' => $asetTidakLancar,
            'liabilitas_pendek' => $liabilitasPendek,
            'liabilitas_panjang' => $liabilitasPanjang,
            'ekuitas' => $ekuitas,
            'totals' => [
                'aset_lancar' => $totalAsetLancar,
                'aset_tidak_lancar' => $totalAsetTidakLancar,
                'aset' => $totalAset,
                'liabilitas_pendek' => $totalLiabilitasPendek,
                'liabilitas_panjang' => $totalLiabilitasPanjang,
                'liabilitas' => $totalLiabilitas,
                'ekuitas' => $totalEkuitas,
                'liabilitas_ekuitas' => $totalLiabilitasEkuitas,
            ],
            'selisih' => $totalAset - $totalLiabilitasEkuitas,
        ];
    }

    protected static function fetchKelompokRows(
        Carbon $asOf,
        KategoriAkun $kategori,
        KelompokNeraca $kelompok,
    ): array {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();
        $kodeAkunList = self::getKodeAkunByKelompok([$kelompok]);

        return InputTransaksiToko::query()
            ->selectRaw("{$jenisAkunTable}.kode_jenis_akun as kode_jenis_akun")
            ->selectRaw("{$jenisAkunTable}.nama_jenis_akun as nama_jenis_akun")
            ->selectRaw("SUM({$transaksiTable}.nominal_transaksi) as total")
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->where("{$transaksiTable}.kategori_transaksi", $kategori->value)
            ->when(
                empty($kodeAkunList),
                fn (Builder $query) => $query->whereRaw('1 = 0'),
                fn (Builder $query) => $query->whereIn("{$kodeAkunTable}.kode_akun", $kodeAkunList),
            )
            ->whereDate("{$transaksiTable}.tanggal_transaksi", '<=', $asOf)
            ->groupBy("{$jenisAkunTable}.id", "{$jenisAkunTable}.kode_jenis_akun", "{$jenisAkunTable}.nama_jenis_akun")
            ->orderBy("{$jenisAkunTable}.kode_jenis_akun")
            ->get()
            ->map(function ($row): array {
                $kode = $row->kode_jenis_akun ?? '-';
                $nama = $row->nama_jenis_akun ?? '-';

                return [
                    'nama' => $nama,
                    'total' => (float) $row->total,
                ];
            })
            ->all();
    }

    protected static function sumRows(array $rows): float
    {
        return array_reduce(
            $rows,
            fn (float $carry, array $row): float => $carry + (float) ($row['total'] ?? 0),
            0.0,
        );
    }

    /**
     * @param array<int, KelompokNeraca> $kelompokList
     * @return array<int, string>
     */
    protected static function getKodeAkunByKelompok(array $kelompokList): array
    {
        if (empty($kelompokList)) {
            return [];
        }

        return collect(self::$kelompokNeracaMapping)
            ->filter(fn (KelompokNeraca $kelompok) => in_array($kelompok, $kelompokList, true))
            ->keys()
            ->values()
            ->all();
    }

    protected static function totalInventoryHpp(Carbon $asOf): float
    {
        $pembelianItemTable = (new PembelianItem())->getTable();
        $pembelianTable = (new Pembelian())->getTable();
        $qtyColumn = PembelianItem::qtySisaColumn();

        $total = PembelianItem::query()
            ->leftJoin($pembelianTable, "{$pembelianTable}.id_pembelian", '=', "{$pembelianItemTable}.id_pembelian")
            ->whereDate("{$pembelianTable}.tanggal", '<=', $asOf)
            ->where("{$pembelianItemTable}.{$qtyColumn}", '>', 0)
            ->selectRaw("SUM(COALESCE({$pembelianItemTable}.hpp, 0) * COALESCE({$pembelianItemTable}.{$qtyColumn}, 0)) as total")
            ->value('total');

        return (float) ($total ?? 0);
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

        return $months[$date->month] ?? $date->format('F');
    }

    protected static function formatTanggal(Carbon $date): string
    {
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

        return $date->format('d') . ' ' . $monthLabel . ' ' . $date->year;
    }

    /**
     * @return array<string, string>
     */
    protected static function yearOptions(): array
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();

        $years = InputTransaksiToko::query()
            ->selectRaw("YEAR({$transaksiTable}.tanggal_transaksi) as tahun")
            ->distinct()
            ->pluck('tahun')
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        return $years->mapWithKeys(fn ($year) => [(string) $year => (string) $year])->all();
    }
}
