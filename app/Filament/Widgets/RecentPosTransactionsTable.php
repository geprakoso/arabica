<?php

namespace App\Filament\Widgets;

use App\Enums\MetodeBayar;
use App\Models\Penjualan;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecentPosTransactionsTable extends AdvancedTableWidget
{
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
                    ->formatStateUsing(fn (?MetodeBayar $state) => $state?->label())
                    ->color(fn (?MetodeBayar $state) => match ($state) {
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
                    ->url(fn (Penjualan $record) => route('filament.pos.resources.pos-activities.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('tanggal_penjualan', 'desc')
            ->paginated(5);
    }

    protected function getTableQuery(): Builder
    {
        return Penjualan::query()
            ->with(['member'])
            ->latest('tanggal_penjualan');
    }
}
