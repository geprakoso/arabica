<?php

namespace App\Filament\Widgets;

use App\Models\PembelianItem;
use App\Models\Produk;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;
use App\Filament\Resources\InventoryResource;

class LowStockProductsTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 6;
    protected ?string $placeholderHeight = '16rem';

    protected static ?string $icon = 'hugeicons-battery-low';
    protected static ?string $heading = 'Produk hampir habis';
    protected static ?string $iconColor = 'danger';
    protected static ?string $description = 'Daftar produk dengan stok terendah pada bulan ini.';

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
                    ->limit(35)
                    ->tooltip(fn(string $state): string => $state),
                Tables\Columns\TextColumn::make('brand.nama_brand')
                    ->label('Brand')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('stok_tersisa')
                    ->label('Stok')
                    ->badge()
                    ->color(fn(int|float|string|null $state) => (int) ($state ?? 0) <= 5 ? 'danger' : 'warning')
                    ->sortable(),
            ])
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inventory')
                ->label('Lihat Inventory')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->url(
                    InventoryResource::getUrl('index', [
                        'tableSortColumn' => 'total_qty',
                        'tableSortDirection' => 'asc',
                    ])
                ),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $produkTable = (new Produk())->getTable();
        $qtyColumn = PembelianItem::qtySisaColumn();

        return Produk::query()
            ->with('brand')
            ->whereHas('pembelianItems', fn($q) => $q->where($qtyColumn, '>', 0))
            ->withSum(['pembelianItems as stok_tersisa' => fn($q) => $q->where($qtyColumn, '>', 0)], $qtyColumn)
            ->orderBy('stok_tersisa')
            ->having('stok_tersisa', '<=', 20);
    }
}
