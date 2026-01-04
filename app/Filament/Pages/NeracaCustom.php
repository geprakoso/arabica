<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Akunting\LaporanNeracaResource;
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
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Illuminate\Contracts\View\View;

class NeracaCustom extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $title = 'Laporan Neraca';
    protected static string $view = 'filament.pages.neraca-custom';
    protected static ?string $navigationGroup = 'Reports';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'as_of_date' => now()->endOfMonth()->toDateString(),
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

    public function getBreadcrumbs(): array
    {
        return [
            LaporanNeracaResource::getUrl('index')
                => LaporanNeracaResource::getBreadcrumb(),
            'Detail',
        ];
    }

    public function getHeader(): ?View
    {
        return view('filament.pages.partials.neraca-custom-header', [
            'heading' => $this->getHeading(),
            'subheading' => $this->getSubheading(),
            'breadcrumbs' => filament()->hasBreadcrumbs() ? $this->getBreadcrumbs() : [],
            'actions' => $this->getCachedHeaderActions(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('as_of_date')
                    ->label('')
                    ->closeOnDateSelection()
                    ->native(false)
                    ->hidden()
                    ->live(),
            ])
            ->statePath('data');
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('as_of_date')
                    ->label('Per Tanggal')
                    ->native(false)
                    ->live(),
            ])
            ->statePath('data');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                ViewEntry::make('neraca_table')
                    ->label('')
                    ->view('filament.infolists.neraca-custom-table')
                    ->state(fn () => $this->reportData())
                    ->extraEntryWrapperAttributes(['class' => 'w-full max-w-none'])
                    ->columnSpanFull(),
            ]);
    }

    protected function reportData(): array
    {
        $asOf = $this->getAsOfDate();
        
        // Create a fake record with the as_of date
        $fakeRecord = (object) [
            'month_start' => $asOf->startOfMonth()->toDateString(),
        ];

        return LaporanNeracaResource::neracaViewData($fakeRecord);
    }

    protected function exportCsv()
    {
        $fileName = $this->exportFileName('csv');
        $rows = $this->buildExportRows();

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Category', 'Item', 'Amount']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['Section'], $row['Category'], $row['Item'], $row['Amount']]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function exportXlsx()
    {
        $fileName = $this->exportFileName('xlsx');
        $rows = $this->buildExportRows();

        $path = sys_get_temp_dir() . '/neraca-' . uniqid('', true) . '.xlsx';

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
        $fileName = $this->exportFileName('pdf');
        $data = $this->reportData();
        $data['header_title'] = 'Laporan Neraca';

        $pdf = Pdf::loadView('exports.neraca-custom-pdf', [
            'data' => $data,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    protected function buildExportRows(): array
    {
        $data = $this->reportData();
        $rows = [];

        // Aset Lancar
        foreach ($data['aset_lancar'] as $row) {
            $rows[] = [
                'Section' => 'Aset',
                'Category' => 'Aset Lancar',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }
        $rows[] = [
            'Section' => 'Aset',
            'Category' => 'Aset Lancar',
            'Item' => 'Total Aset Lancar',
            'Amount' => $data['totals']['aset_lancar'],
        ];

        // Aset Tidak Lancar
        foreach ($data['aset_tidak_lancar'] as $row) {
            $rows[] = [
                'Section' => 'Aset',
                'Category' => 'Aset Tidak Lancar',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }
        $rows[] = [
            'Section' => 'Aset',
            'Category' => 'Aset Tidak Lancar',
            'Item' => 'Total Aset Tidak Lancar',
            'Amount' => $data['totals']['aset_tidak_lancar'],
        ];

        $rows[] = [
            'Section' => 'Aset',
            'Category' => 'Total',
            'Item' => 'Total Aset Keseluruhan',
            'Amount' => $data['totals']['aset'],
        ];

        // Liabilitas Pendek
        foreach ($data['liabilitas_pendek'] as $row) {
            $rows[] = [
                'Section' => 'Liabilitas',
                'Category' => 'Jangka Pendek',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }
        $rows[] = [
            'Section' => 'Liabilitas',
            'Category' => 'Jangka Pendek',
            'Item' => 'Total Liabilitas Jangka Pendek',
            'Amount' => $data['totals']['liabilitas_pendek'],
        ];

        // Liabilitas Panjang
        foreach ($data['liabilitas_panjang'] as $row) {
            $rows[] = [
                'Section' => 'Liabilitas',
                'Category' => 'Jangka Panjang',
                'Item' => $row['nama'],
                'Amount' => $row['total'],
            ];
        }
        $rows[] = [
            'Section' => 'Liabilitas',
            'Category' => 'Jangka Panjang',
            'Item' => 'Total Liabilitas Jangka Panjang',
            'Amount' => $data['totals']['liabilitas_panjang'],
        ];

        $rows[] = [
            'Section' => 'Liabilitas',
            'Category' => 'Total',
            'Item' => 'Total Liabilitas Keseluruhan',
            'Amount' => $data['totals']['liabilitas'],
        ];

        return $rows;
    }

    protected function exportFileName(string $extension): string
    {
        $date = $this->getAsOfDate();
        $dateLabel = $date->format('Ymd');

        return "neraca-{$dateLabel}.{$extension}";
    }

    protected function getAsOfDate(): Carbon
    {
        $asOfInput = $this->data['as_of_date'] ?? null;
        return filled($asOfInput) ? Carbon::parse($asOfInput)->endOfDay() : now()->endOfMonth();
    }
}
