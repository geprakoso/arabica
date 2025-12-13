<?php

namespace App\Filament\Exports;

use App\Enums\KategoriAkun;
use App\Models\InputTransaksiToko;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InputTransaksiTokoExporter extends Exporter
{
    protected static ?string $model = InputTransaksiToko::class;

    /**
     * Force queued processing so completion notifications disimpan di notification center
     * meskipun queue default diset ke "sync".
     */
    public function getJobConnection(): ?string
    {
        return config('queue.default') === 'sync'
            ? 'database'
            : null;
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('tanggal_transaksi')
                ->label('Tanggal')
                ->formatStateUsing(fn ($state) => optional($state)?->format('Y-m-d')),

            ExportColumn::make('kategori_transaksi')
                ->label('Kategori')
                ->formatStateUsing(function ($state) {
                    $enum = $state instanceof KategoriAkun ? $state : KategoriAkun::tryFrom($state);
                    return $enum?->getLabel() ?? $state;
                }),

            ExportColumn::make('jenisAkun.nama_jenis_akun')
                ->label('Jenis Akun'),

            ExportColumn::make('akunTransaksi.nama_akun')
                ->label('Akun Transaksi'),

            ExportColumn::make('nominal_transaksi')
                ->label('Nominal (IDR)')
                ->formatStateUsing(fn ($state) => $state !== null
                    ? 'Rp ' . number_format((float) $state, 2, ',', '.')
                    : 'Rp 0,00'),

            ExportColumn::make('keterangan_transaksi')
                ->label('Keterangan'),

            ExportColumn::make('user.name')
                ->label('Penginput'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor Input Transaksi Toko selesai. '
            . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
