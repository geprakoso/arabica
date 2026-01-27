<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use App\Filament\Actions\InventoryExportHeaderAction;
use App\Filament\Resources\InventoryResource;
use App\Filament\Resources\StockOpnameResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Produk;

class ListStockOpnames extends ListRecords
{
    protected static string $resource = StockOpnameResource::class;

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->headerActions([
                \App\Filament\Actions\InventoryExportHeaderAction::make('export_inventory_stock_pdf')
                    ->label('Download Inventory Stock PDF')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->fileName('Inventory Stock ' . '_' . date('d M Y'))
                    ->defaultFormat('pdf')
                    ->disableTableColumns()
                    ->modalHeading(false)
                    ->withColumns([
                        TextColumn::make('sku')
                            ->label('SKU'),
                        TextColumn::make('nama_produk')
                            ->label('Nama Produk'),
                        TextColumn::make('brand.nama_brand')
                            ->label('Brand'),
                        TextColumn::make('kategori.nama_kategori')
                            ->label('Kategori'),
                        TextColumn::make('total_qty')
                            ->label('Stok Sistem')
                            ->state(fn(Produk $record) => (int) ($record->total_qty ?? 0)),
                        TextColumn::make('latest_batch.hpp')
                            ->label('HPP Terkini')
                            ->state(fn(Produk $record) => InventoryResource::getInventorySnapshot($record)['latest_batch']['hpp'] ?? null),
                        TextColumn::make('latest_batch.harga_jual')
                            ->label('Harga Jual Terkini')
                            ->state(fn(Produk $record) => InventoryResource::getInventorySnapshot($record)['latest_batch']['harga_jual'] ?? null),
                        TextColumn::make('stok_opname')
                            ->label('Stok Opname')
                            ->state(fn() => null),
                        TextColumn::make('selisih')
                            ->label('Selisih')
                            ->state(fn() => null),
                    ])
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Stok Opname',
                        'printed_by' => Auth::user()?->name ?? '-',
                        'printed_at' => now()->format('d M Y H:i'),
                        'sort_key' => 'kategori.nama_kategori',
                        'group_by' => 'kategori.nama_kategori',
                        'group_label' => 'Kategori',
                    ])
                    ->summaryResolver(fn (Builder $query, Collection $records) => InventoryResource::buildExportSummary($records)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Stock Opname')
                ->icon('heroicon-o-plus'),
        ];
    }
}
