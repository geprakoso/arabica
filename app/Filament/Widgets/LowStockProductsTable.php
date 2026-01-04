<?php

namespace App\Filament\Widgets;

use App\Models\PembelianItem;
use App\Models\Produk;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class LowStockProductsTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos';
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stok Hampir Habis')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->wrap()
                    ->description(fn(Produk $record) => $record->sku ? 'SKU: ' . $record->sku : null),
                Tables\Columns\TextColumn::make('brand.nama_brand')
                    ->label('Brand')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stok_tersisa')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(int|float|string|null $state) => (int) ($state ?? 0) <= 5 ? 'danger' : 'warning')
                    ->sortable(),
            ])
            ->paginated(5);
    }

    protected function getTableQuery(): Builder
    {
        $produkTable = (new Produk())->getTable();
        $qtyColumn = PembelianItem::qtySisaColumn();

        return Produk::query()
            ->with('brand')
            ->whereHas('pembelianItems', fn ($q) => $q->where($qtyColumn, '>', 0))
            ->withSum(['pembelianItems as stok_tersisa' => fn ($q) => $q->where($qtyColumn, '>', 0)], $qtyColumn)
            ->orderBy('stok_tersisa')
            ->having('stok_tersisa', '<=', 20);
    }
}
