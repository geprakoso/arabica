<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActiveMembersTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Member Belanja Bulan Ini')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nama_member')
                    ->label('Member')
                    ->searchable()
                    ->wrap()
                    ->description(fn($record) => $record->no_hp ? 'Telp: ' . $record->no_hp : null),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Transaksi')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Belanja')
                    ->money('idr', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_purchase')
                    ->label('Terakhir')
                    ->date()
                    ->sortable(),
            ])
            ->paginated(10);
    }

    protected function getTableQuery(): Builder
    {
        $membersTable = (new Member())->getTable();
        $salesTable = (new Penjualan())->getTable();
        [$start, $end] = $this->currentMonthRange();

        return Member::query()
            ->select([
                "{$membersTable}.id",
                "{$membersTable}.nama_member",
                "{$membersTable}.no_hp",
                DB::raw('COUNT(' . $salesTable . '.id_penjualan) as orders_count'),
                DB::raw('COALESCE(SUM(' . $salesTable . '.grand_total), 0) as total_spent'),
                DB::raw('MAX(' . $salesTable . '.tanggal_penjualan) as last_purchase'),
            ])
            ->join($salesTable, "{$salesTable}.id_member", '=', "{$membersTable}.id")
            ->whereBetween("{$salesTable}.tanggal_penjualan", [$start, $end])
            ->groupBy("{$membersTable}.id", "{$membersTable}.nama_member", "{$membersTable}.no_hp")
            ->orderByDesc('last_purchase')
            ->limit(7);
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
