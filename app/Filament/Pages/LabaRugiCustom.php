<?php

namespace App\Filament\Pages;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use App\Models\JenisAkun;
use App\Models\KodeAkun;
use App\Models\PembelianItem;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Spatie\SimpleExcel\SimpleExcelWriter;

class LabaRugiCustom extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Laporan Laba Rugi';
    protected static string $view = 'filament.pages.laba-rugi-custom';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);
    }

    protected function getForms(): array
    {
        return [
            'form',
            'filtersForm',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->action(fn () => $this->exportCsv()),
                Action::make('exportXlsx')
                    ->label('Export Excel')
                    ->action(fn () => $this->exportXlsx()),
                Action::make('exportPdf')
                    ->label('Export PDF')
                    ->action(fn () => $this->exportPdf()),
            ])
                ->label('Export')
                ->icon('hugeicons-share-08')
                ->button(),
        ];
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            \App\Filament\Resources\LaporanLabaRugiResource::getUrl('index')
                => \App\Filament\Resources\LaporanLabaRugiResource::getBreadcrumb(),
            'Detail',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('')
                    ->closeOnDateSelection()
                    ->native(false)
                    ->hidden()
                    ->live(),
                DatePicker::make('end_date')
                    ->label('')
                    ->closeOnDateSelection()
                    ->native(false)
                    ->hidden()
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('')
                    ->prefix('Mulai')
                    ->maxDate(fn (callable $get) => $get('end_date'))
                    ->closeOnDateSelection()
                    ->native(false)
                    ->live(),
                DatePicker::make('end_date')
                    ->label('')
                    ->prefix('Sampai')
                    ->minDate(fn (callable $get) => $get('start_date'))
                    ->closeOnDateSelection()
                    ->native(false)
                    ->live(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                ViewEntry::make('laba_rugi_table')
                    ->label('')
                    ->view('filament.infolists.laba-rugi-custom-table')
                    ->state(fn () => $this->reportData())
                    ->extraEntryWrapperAttributes(['class' => 'w-full max-w-none'])
                    ->columnSpanFull(),
            ]);
    }

    // public function getMaxContentWidth(): MaxWidth
    // {
    //     return MaxWidth::Full;
    // }

    protected function reportData(): array
    {
        [$start, $end] = $this->getDateRange();

        $totalPenjualan = $this->totalPenjualan($start, $end);
        $totalHpp = $this->totalHpp($start, $end);
        $labaKotor = $totalPenjualan - $totalHpp;

        $bebanRows = $this->fetchBebanUsahaRows($start, $end);
        $totalBeban = $this->sumRows($bebanRows);
        $labaUsaha = $labaKotor - $totalBeban;

        $pendapatanLainRows = $this->fetchPendapatanLainRows($start, $end);
        $totalPendapatanLain = $this->sumRows($pendapatanLainRows);

        $labaSebelumPajak = $labaUsaha + $totalPendapatanLain;
        $tax = 0.0;
        $labaBersih = $labaSebelumPajak + $tax;

        return [
            'company_name' => config('app.name'),
            'periode_label' => $this->formatPeriodeLabel($start, $end),
            'total_penjualan' => $totalPenjualan,
            'total_hpp' => $totalHpp,
            'laba_kotor' => $labaKotor,
            'beban_rows' => $bebanRows,
            'total_beban' => $totalBeban,
            'laba_usaha' => $labaUsaha,
            'pendapatan_lain_rows' => $pendapatanLainRows,
            'total_pendapatan_lain' => $totalPendapatanLain,
            'laba_sebelum_pajak' => $labaSebelumPajak,
            'tax' => $tax,
            'laba_bersih' => $labaBersih,
        ];
    }

    protected function exportCsv()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = $this->exportFileName('csv', $start, $end);
        $rows = $this->buildExportRows($start, $end);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Item', 'Amount']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['Section'], $row['Item'], $row['Amount']]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function exportXlsx()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = $this->exportFileName('xlsx', $start, $end);
        $rows = $this->buildExportRows($start, $end);

        $path = sys_get_temp_dir() . '/laba-rugi-' . uniqid('', true) . '.xlsx';

        SimpleExcelWriter::create($path)
            ->addRows($rows)
            ->close();

        return response()->download(
            $path,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    protected function exportPdf()
    {
        [$start, $end] = $this->getDateRange();
        $fileName = $this->exportFileName('pdf', $start, $end);
        $data = $this->reportData();

        $pdf = Pdf::loadView('exports.laba-rugi-custom-pdf', [
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function getDateRange(): array
    {
        $startInput = $this->data['start_date'] ?? null;
        $endInput = $this->data['end_date'] ?? null;

        $start = filled($startInput) ? Carbon::parse($startInput) : now()->startOfMonth();
        $end = filled($endInput) ? Carbon::parse($endInput) : now()->endOfMonth();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [$start->copy()->startOfDay(), $end->copy()->endOfDay()];
    }

    protected function totalPenjualan(Carbon $start, Carbon $end): float
    {
        $produkTotal = PenjualanItem::query()
            ->whereHas('penjualan', fn ($query) => $query->whereBetween('tanggal_penjualan', [$start, $end]))
            ->selectRaw('SUM(COALESCE(harga_jual, 0) * COALESCE(qty, 0)) as total')
            ->value('total') ?? 0;

        $jasaTotal = PenjualanJasa::query()
            ->whereHas('penjualan', fn ($query) => $query->whereBetween('tanggal_penjualan', [$start, $end]))
            ->selectRaw('SUM(COALESCE(harga, 0) * (CASE WHEN qty IS NULL OR qty < 1 THEN 1 ELSE qty END)) as total')
            ->value('total') ?? 0;

        return (float) $produkTotal + (float) $jasaTotal;
    }

    protected function totalHpp(Carbon $start, Carbon $end): float
    {
        $total = PembelianItem::query()
            ->whereHas('pembelian', fn ($query) => $query->whereBetween('tanggal', [$start, $end]))
            ->selectRaw('SUM(COALESCE(hpp, 0) * COALESCE(qty, 0)) as total')
            ->value('total') ?? 0;

        return (float) $total;
    }

    protected function fetchKategoriRows(Carbon $start, Carbon $end, KategoriAkun $kategori): array
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();

        return InputTransaksiToko::query()
            ->selectRaw("{$kodeAkunTable}.kode_akun as kode_akun")
            ->selectRaw("{$kodeAkunTable}.nama_akun as nama_akun")
            ->selectRaw("SUM({$transaksiTable}.nominal_transaksi) as total")
            ->leftJoin($jenisAkunTable, "{$jenisAkunTable}.id", '=', "{$transaksiTable}.kode_jenis_akun_id")
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->whereRaw(
                "LOWER(COALESCE({$transaksiTable}.kategori_transaksi, {$kodeAkunTable}.kategori_akun)) = ?",
                [$kategori->value]
            )
            ->whereBetween("{$transaksiTable}.tanggal_transaksi", [$start, $end])
            ->groupBy("{$kodeAkunTable}.id", "{$kodeAkunTable}.kode_akun", "{$kodeAkunTable}.nama_akun")
            ->orderBy("{$kodeAkunTable}.kode_akun")
            ->get()
            ->map(function ($row): array {
                $kode = $row->kode_akun ?? '-';
                $nama = $row->nama_akun ?? '-';

                return [
                    'nama' => "{$kode} - {$nama}",
                    'total' => (float) $row->total,
                ];
            })
            ->all();
    }

    protected function fetchBebanUsahaRows(Carbon $start, Carbon $end): array
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();
        $kodeBebanUsaha = ['51', '52', '61', '81'];

        return JenisAkun::query()
            ->selectRaw("{$jenisAkunTable}.kode_jenis_akun as kode_jenis_akun")
            ->selectRaw("{$jenisAkunTable}.nama_jenis_akun as nama_jenis_akun")
            ->selectRaw("COALESCE(SUM({$transaksiTable}.nominal_transaksi), 0) as total")
            ->leftJoin($transaksiTable, function ($join) use ($jenisAkunTable, $transaksiTable, $start, $end): void {
                $join->on("{$transaksiTable}.kode_jenis_akun_id", '=', "{$jenisAkunTable}.id")
                    ->whereBetween("{$transaksiTable}.tanggal_transaksi", [$start, $end]);
            })
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->whereIn("{$kodeAkunTable}.kode_akun", $kodeBebanUsaha)
            ->groupBy("{$jenisAkunTable}.id", "{$jenisAkunTable}.kode_jenis_akun", "{$jenisAkunTable}.nama_jenis_akun")
            ->havingRaw("COALESCE(SUM({$transaksiTable}.nominal_transaksi), 0) <> 0")
            ->orderBy("{$jenisAkunTable}.kode_jenis_akun")
            ->get()
            ->map(function ($row): array {
                $nama = $row->nama_jenis_akun ?? '-';

                return [
                    'nama' => $nama,
                    'total' => (float) $row->total,
                ];
            })
            ->all();
    }

    protected function fetchPendapatanLainRows(Carbon $start, Carbon $end): array
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();
        $jenisAkunTable = (new JenisAkun())->getTable();
        $kodeAkunTable = (new KodeAkun())->getTable();
        $kodePendapatanLain = ['41', '71'];

        return JenisAkun::query()
            ->selectRaw("{$jenisAkunTable}.kode_jenis_akun as kode_jenis_akun")
            ->selectRaw("{$jenisAkunTable}.nama_jenis_akun as nama_jenis_akun")
            ->selectRaw("COALESCE(SUM({$transaksiTable}.nominal_transaksi), 0) as total")
            ->leftJoin($transaksiTable, function ($join) use ($jenisAkunTable, $transaksiTable, $start, $end): void {
                $join->on("{$transaksiTable}.kode_jenis_akun_id", '=', "{$jenisAkunTable}.id")
                    ->whereBetween("{$transaksiTable}.tanggal_transaksi", [$start, $end]);
            })
            ->leftJoin($kodeAkunTable, "{$kodeAkunTable}.id", '=', "{$jenisAkunTable}.kode_akun_id")
            ->whereIn("{$kodeAkunTable}.kode_akun", $kodePendapatanLain)
            ->groupBy("{$jenisAkunTable}.id", "{$jenisAkunTable}.kode_jenis_akun", "{$jenisAkunTable}.nama_jenis_akun")
            ->havingRaw("COALESCE(SUM({$transaksiTable}.nominal_transaksi), 0) <> 0")
            ->orderBy("{$jenisAkunTable}.kode_jenis_akun")
            ->get()
            ->map(function ($row): array {
                $nama = $row->nama_jenis_akun ?? '-';

                return [
                    'nama' => $nama,
                    'total' => (float) $row->total,
                ];
            })
            ->all();
    }

    protected function sumRows(array $rows): float
    {
        return array_reduce(
            $rows,
            fn (float $carry, array $row): float => $carry + (float) ($row['total'] ?? 0),
            0.0,
        );
    }

    protected function buildExportRows(Carbon $start, Carbon $end): array
    {
        $data = $this->reportData();
        $rows = [];

        $rows[] = [
            'Section' => 'Pendapatan',
            'Item' => 'Total Penjualan',
            'Amount' => $data['total_penjualan'],
        ];
        $rows[] = [
            'Section' => 'Beban Pokok Penjualan',
            'Item' => 'Harga Pokok Penjualan',
            'Amount' => $data['total_hpp'],
        ];
        $rows[] = [
            'Section' => 'Laba Kotor',
            'Item' => 'Laba Kotor',
            'Amount' => $data['laba_kotor'],
        ];

        foreach ($data['beban_rows'] as $row) {
            $rows[] = [
                'Section' => 'Beban Usaha',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }

        $rows[] = [
            'Section' => 'Laba Usaha',
            'Item' => 'Laba Usaha',
            'Amount' => $data['laba_usaha'],
        ];

        foreach ($data['pendapatan_lain_rows'] as $row) {
            $rows[] = [
                'Section' => 'Pendapatan Lain-lain',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }

        $rows[] = [
            'Section' => 'Laba Sebelum Pajak',
            'Item' => 'Laba Sebelum Pajak',
            'Amount' => $data['laba_sebelum_pajak'],
        ];
        $rows[] = [
            'Section' => 'Laba Bersih',
            'Item' => 'Laba Bersih',
            'Amount' => $data['laba_bersih'],
        ];

        return $rows;
    }

    protected function exportFileName(string $extension, Carbon $start, Carbon $end): string
    {
        $startLabel = $start->format('Ymd');
        $endLabel = $end->format('Ymd');

        return "laba-rugi-{$startLabel}-{$endLabel}.{$extension}";
    }

    protected function formatPeriodeLabel(Carbon $start, Carbon $end): string
    {
        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $this->formatMonthLabel($start) . ' ' . $start->year;
        }

        return $this->formatTanggal($start) . ' - ' . $this->formatTanggal($end);
    }

    protected function formatMonthLabel(Carbon $date): string
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

        return $months[$date->month] ?? $date->format('F');
    }

    protected function formatTanggal(Carbon $date): string
    {
        $monthLabel = $this->formatMonthLabel($date);

        return $date->format('d') . ' ' . $monthLabel . ' ' . $date->year;
    }
}
