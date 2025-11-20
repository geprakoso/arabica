<?php

namespace App\Filament\Exports;

use App\Models\Penjualan;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class PenjualanExporter extends Exporter
{
    protected static ?string $model = Penjualan::class;

    /**
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('no_nota')
                ->label('No Nota'),
            ExportColumn::make('tanggal_penjualan')
                ->label('Tanggal')
                ->formatStateUsing(fn (?Penjualan $record) => optional($record?->tanggal_penjualan)->format('Y-m-d')),
            ExportColumn::make('member.nama_member')
                ->label('Member'),
            ExportColumn::make('karyawan.nama_karyawan')
                ->label('Karyawan'),
            ExportColumn::make('total_qty')
                ->label('Total Qty')
                ->state(fn (Penjualan $record) => $record->items->sum('qty')),
            ExportColumn::make('total_penjualan')
                ->label('Total Penjualan')
                ->state(fn (Penjualan $record) => $record->items->sum(fn ($item) => (float) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0))),
            ExportColumn::make('total_hpp')
                ->label('Total HPP')
                ->state(fn (Penjualan $record) => $record->items->sum(fn ($item) => (float) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))),
            ExportColumn::make('total_margin')
                ->label('Margin')
                ->state(fn (Penjualan $record) => $record->items->sum(fn ($item) => ((float) ($item->harga_jual ?? 0) - (float) ($item->hpp ?? 0)) * (int) ($item->qty ?? 0))),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['items', 'member', 'karyawan']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Ekspor laporan penjualan telah selesai dan siap diunduh.';
    }
}
