<?php

namespace App\Filament\Exports;

use App\Models\Pembelian;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class PembelianExporter extends Exporter
{
    protected static ?string $model = Pembelian::class;

    /**
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('no_po')
                ->label('No PO'),
            ExportColumn::make('tanggal')
                ->label('Tanggal')
                ->formatStateUsing(fn (?Pembelian $record) => optional($record?->tanggal)->format('Y-m-d')),
            ExportColumn::make('supplier.nama_supplier')
                ->label('Supplier'),
            ExportColumn::make('karyawan.nama_karyawan')
                ->label('Karyawan'),
            ExportColumn::make('total_items')
                ->label('Jumlah Item')
                ->state(fn (Pembelian $record) => $record->items->sum('qty')),
            ExportColumn::make('total_hpp')
                ->label('Total HPP')
                ->state(fn (Pembelian $record) => $record->items->sum(fn ($item) => (int) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))),
            ExportColumn::make('total_harga_jual')
                ->label('Total Harga Jual')
                ->state(fn (Pembelian $record) => $record->items->sum(fn ($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0))),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['items', 'supplier', 'karyawan']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor laporan pembelian telah selesai dan tersedia untuk diunduh.';
    }
}
