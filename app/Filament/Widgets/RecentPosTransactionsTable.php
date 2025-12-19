<?php

namespace App\Filament\Widgets;

use App\Enums\MetodeBayar;
use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PosActivityResource;

class RecentPosTransactionsTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 7;
    protected static ?string $pollingInterval = '30s';
    protected ?string $placeholderHeight = '18rem';
    protected static ?string $icon = 'heroicon-o-wallet';
    protected static ?string $heading = 'Transaksi Terbaru';
    protected static ?string $iconColor = 'primary';
    protected static ?string $description = 'Daftar transaksi terbaru pada sistem.';

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query($this->getTableQuery()
                ->limit(5)
                )
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')
                    ->label('Nota'),
                Tables\Columns\TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('idr', true),
                Tables\Columns\TextColumn::make('metode_bayar')
                    ->label('Bayar')
                    ->badge()
                    ->icon(fn(MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'heroicon-o-currency-dollar',
                        MetodeBayar::CARD => 'heroicon-o-credit-card',
                        MetodeBayar::TRANSFER => 'heroicon-o-banknotes',
                        MetodeBayar::EWALLET => 'heroicon-o-wallet',
                        default => 'heroicon-o-question-mark-circle ',
                    })
                    ->formatStateUsing(fn(?MetodeBayar $state) => $state?->label())
                    ->color(fn(?MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'success',
                        MetodeBayar::CARD => 'info',
                        MetodeBayar::TRANSFER => 'gray',
                        MetodeBayar::EWALLET => 'warning',
                        default => 'secondary',
                    }),
            ])
            ->recordAction('view')
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(false)
                    ->icon(null)
                    ->slideOver()
                    ->modalHeading(fn (Penjualan $record) => $record->no_nota)
                    ->modalWidth('6xl')
                    ->infolist(fn (Infolist $infolist) => PosActivityResource::infolist($infolist)),
            ])
            ->defaultSort('tanggal_penjualan', 'desc')
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('posActivities')
                ->label('Lihat Aktivitas POS')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->url(
                    PosActivityResource::getUrl('index', [
                        'tableSortColumn' => 'tanggal_penjualan',
                        'tableSortDirection' => 'desc',
                    ])
                ),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Penjualan::query()
            ->with(['member'])
            ->latest('tanggal_penjualan');
    }
}
