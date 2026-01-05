<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\MasterData\MemberResource;
use Filament\Facades\Filament;

class ActiveMembersTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 4;
    protected ?string $placeholderHeight = '16rem';

    protected static ?string $icon = 'hugeicons-ai-user';
    protected static ?string $heading = 'Member Aktif';
    protected static ?string $iconColor = 'primary';
    protected static ?string $description = 'Daftar member yang paling aktif belanja bulan ini.';

    // public static function canView(): bool
    // {
    //     return Filament::getCurrentPanel()?->getId() === 'pos';
    // }

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('nama_member')
                    ->label('Member')
                    ->wrap(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Transaksi')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Belanja')
                    ->formatStateUsing(fn($state) => money($state, 'IDR')->formatWithoutZeroes())
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_purchase')
                    ->label('Terakhir')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(false)
                    ->icon(null)
                    ->slideOver()
                    ->modalHeading(fn(Member $record) => $record->nama_member)
                    ->modalWidth('6xl')
                    ->infolist(fn(Infolist $infolist) => MemberResource::infolist($infolist)),
            ])
            ->paginated(false);
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
