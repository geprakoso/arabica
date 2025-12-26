<?php

namespace App\Filament\Widgets;

use App\Enums\MetodeBayar;
use App\Models\Penjualan;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class RecentPosTransactionsTable extends AdvancedTableWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 7;
    protected static ?string $pollingInterval = '30s';
    protected static ?string $icon = 'heroicon-o-wallet';
    protected static ?string $heading = 'Transaksi Terbaru';
    protected static ?string $iconColor = 'primary';
    protected static ?string $description = 'Daftar transaksi terbaru pada sistem.';

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')
                    ->label('Nota')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nota tersalin')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sumber_transaksi')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => strtoupper($state ?? 'POS'))
                    ->color(fn(?string $state) => $state === 'manual' ? 'gray' : 'primary')
                    ->tooltip(fn(?string $state) => $state === 'manual' ? 'Input melalui Penjualan' : 'Input melalui POS'),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->formatStateUsing(function ($state, Penjualan $record) {
                        $produkTotal = $record->items->sum(fn($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0));
                        $jasaTotal = $record->jasaItems->sum(fn($service) => (int) ($service->harga ?? 0));
                        $diskon = (int) ($record->diskon_total ?? 0);
                        $computed = max(0, ($produkTotal + $jasaTotal) - $diskon);
                        $total = ($state ?? 0) > 0 ? $state : $computed;

                        return money($total * 100, 'IDR')->formatWithoutZeroes();
                    }),
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
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Penjualan $record) => route('filament.pos.resources.pos-activities.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(5);
    }

    protected function getTableQuery(): Builder
    {
        $query = Penjualan::query()
            ->with(['member', 'items', 'jasaItems'])
            ->latest('created_at');

        return Filament::getCurrentPanel()?->getId() === 'pos'
            ? $query->posOnly()
            : $query;
    }
}
