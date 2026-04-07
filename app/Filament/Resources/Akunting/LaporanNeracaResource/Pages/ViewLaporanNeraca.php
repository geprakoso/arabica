<?php

namespace App\Filament\Resources\Akunting\LaporanNeracaResource\Pages;

use App\Filament\Resources\Akunting\LaporanNeracaResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\MaxWidth;

class ViewLaporanNeraca extends ViewRecord
{
    protected static string $resource = LaporanNeracaResource::class;

    public function getTitle(): string
    {
        $monthLabel = LaporanNeracaResource::formatMonthLabel($this->getRecord()?->month_start);

        if ($monthLabel === '-') {
            return 'Laporan Neraca';
        }

        return "Laporan Neraca {$monthLabel}";
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->action(fn () => $this->exportCsv()),
                \Filament\Actions\Action::make('exportXlsx')
                    ->label('Export Excel')
                    ->action(fn () => $this->exportXlsx()),
                \Filament\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->action(fn () => $this->exportPdf()),
            ])
                ->label('Export')
                ->icon('hugeicons-share-08')
                ->button(),
        ];
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

        \Spatie\SimpleExcel\SimpleExcelWriter::create($path)
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
        $data = LaporanNeracaResource::neracaViewData($this->getRecord());

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.neraca-pdf', [
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
        $data = LaporanNeracaResource::neracaViewData($this->getRecord());
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
        $record = $this->getRecord();
        $monthStart = $record?->month_start;
        $date = $monthStart ? \Carbon\Carbon::parse($monthStart) : now();
        $periode = $date->format('Y-m');

        return "neraca-{$periode}.{$extension}";
    }

    // public function getMaxContentWidth(): MaxWidth
    // {
    //     return MaxWidth::Full;
    // }
}
