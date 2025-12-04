<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Produk;
use Carbon\Carbon;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopSellingProductsTable extends AdvancedTableWidget
{
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Produk Terlaris Bulan Ini')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->wrap()
                    ->description(fn (Produk $record) => $record->sku ? 'SKU: ' . $record->sku : null),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Qty')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Omzet')
                    ->money('idr', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_sold_at')
                    ->label('Terakhir Terjual')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->label('Periode')
                    ->options($this->getMonthOptions())
                    ->default(now()->format('Y-m'))
                    ->placeholder('Pilih bulan')
                    ->reactive()
                    ->query(fn (Builder $query, array $state) => $query),
            ])
            ->defaultSort('total_qty', 'desc')
            ->paginated(5);
    }

    protected function getTableQuery(): Builder
    {
        $itemsTable = (new PenjualanItem())->getTable();
        $salesTable = (new Penjualan())->getTable();
        $productsTable = (new Produk())->getTable();
        [$start, $end] = $this->resolveSelectedPeriodRange();

        return Produk::query()
            ->select([
                "{$productsTable}.id",
                "{$productsTable}.nama_produk",
                "{$productsTable}.sku",
                DB::raw("SUM({$itemsTable}.qty) as total_qty"),
                DB::raw("SUM({$itemsTable}.qty * {$itemsTable}.harga_jual) as total_amount"),
                DB::raw("MAX({$salesTable}.tanggal_penjualan) as last_sold_at"),
            ])
            ->join($itemsTable, "{$itemsTable}.id_produk", '=', "{$productsTable}.id")
            ->join($salesTable, "{$salesTable}.id_penjualan", '=', "{$itemsTable}.id_penjualan")
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->groupBy("{$productsTable}.id", "{$productsTable}.nama_produk", "{$productsTable}.sku")
            ->having('total_qty', '>', 0)
            ->orderByDesc('total_qty');
    }

    protected function resolveSelectedPeriodRange(): array
    {
        $selected = $this->getTableFilterState('period') ?? now()->format('Y-m');

        try {
            $date = Carbon::createFromFormat('Y-m', $selected)->startOfMonth();
        } catch (\Throwable $e) {
            $date = now()->startOfMonth();
        }

        return [$date, $date->copy()->endOfMonth()];
    }

    protected function getMonthOptions(): array
    {
        $options = [];

        for ($i = 0; $i < 6; $i++) {
            $date = now()->copy()->subMonths($i);
            $options[$date->format('Y-m')] = $date->translatedFormat('F Y');
        }

        return $options;
    }

}
