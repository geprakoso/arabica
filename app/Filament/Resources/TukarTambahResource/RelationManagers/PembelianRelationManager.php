<?php

namespace App\Filament\Resources\TukarTambahResource\RelationManagers;

use App\Filament\Resources\PembelianResource;
use App\Models\Pembelian;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PembelianRelationManager extends RelationManager
{
    protected static string $relationship = 'pembelian';

    protected static ?string $title = 'Pembelian';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with('items'))
            ->recordUrl(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
            ->openRecordUrlInNewTab()
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->icon('heroicon-m-document-text')
                    ->weight('bold')
                    ->copyable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray'),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->placeholder('-'),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->placeholder('-'),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->alignRight()
                    ->state(fn(Pembelian $record): string => (string) ($record->items
                        ? (int) $record->items->sum(fn($item) => (int) ($item->qty ?? 0))
                        : 0)),
                TextColumn::make('grand_total_display')
                    ->label('Grand Total')
                    ->alignRight()
                    ->weight('bold')
                    ->color('success')
                    ->state(function (Pembelian $record): string {
                        $total = $record->items
                            ? $record->items->sum(fn($item) => (int) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))
                            : 0;

                        return 'Rp ' . number_format((int) $total, 0, ',', '.');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view')
                    ->label('Show')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Pembelian $record) => PembelianResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ]);
    }
}
