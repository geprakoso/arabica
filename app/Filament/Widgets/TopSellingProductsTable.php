<?php

namespace App\Filament\Widgets;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Produk;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\InventoryResource;




class TopSellingProductsTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 5;
    protected static ?string $pollingInterval = null;
    protected ?string $placeholderHeight = '16rem';

    protected static ?string $icon = 'heroicon-o-chevron-double-up';
    protected static ?string $heading = 'Produk Terlaris Bulan Ini';
    protected static ?string $iconColor = 'primary';
    protected static ?string $description = 'Daftar produk dengan penjualan tertinggi pada bulan berjalan.';


    // public static function canView(): bool
    // {
    //     return Filament::getCurrentPanel()?->getId() === 'pos';
    // }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query(
                $this->getTableQuery()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->wrap()
                    ->limit(35)
                    ->description(fn(Produk $record) => $record->sku ? 'SKU: ' . $record->sku : null),
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Qty')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Omzet')
                    ->formatStateUsing(fn($state) => money($state, 'idr', false)->formatWithoutZeroes())
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_sold_at')
                    ->label('Terakhir Terjual')
                    ->date()
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('total_qty', 'desc')
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(false)
                    ->icon(null)
                    ->slideOver()
                    ->modalHeading(fn(Produk $record) => $record->nama_produk)
                    ->modalWidth('6xl')
                    ->infolist(fn(Infolist $infolist) => InventoryResource::infolist($infolist)),
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        $itemsTable = (new PenjualanItem())->getTable();
        $salesTable = (new Penjualan())->getTable();
        $productsTable = (new Produk())->getTable();
        [$start, $end] = $this->currentMonthRange();

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

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    protected function currentMonthRange(): array
    {
        $now = now();

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }
}
