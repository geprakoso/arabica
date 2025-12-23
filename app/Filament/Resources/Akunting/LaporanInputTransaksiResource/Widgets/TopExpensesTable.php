<?php

namespace App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Widgets;

use App\Enums\KategoriAkun;
use App\Filament\Resources\Akunting\LaporanInputTransaksiResource;
use App\Models\InputTransaksiToko;
use Carbon\Carbon;
use EightyNine\FilamentAdvancedWidget\AdvancedTableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TopExpensesTable extends AdvancedTableWidget
{
    public static function canView(): bool
    {
        return false;
    }

    protected static ?string $heading = 'Pengeluaran Terbesar (Bulan Ini)';

    protected static ?string $description = 'Pantau beban terbesar dan lengkapi bukti sebelum closing.';

    protected static ?string $icon = 'heroicon-o-receipt-percent';

    protected static ?string $iconColor = 'danger';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('')
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->date('d M')
                    ->sortable(),
                Tables\Columns\TextColumn::make('akunTransaksi.nama_akun')
                    ->label('Akun / Cost Center')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nominal_transaksi')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Input oleh')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('bukti_transaksi')
                    ->label('Bukti')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->url(fn (InputTransaksiToko $record) => LaporanInputTransaksiResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('nominal_transaksi', 'desc')
            ->paginated(8);
    }

    protected function getTableQuery(): Builder
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfDay();

        return InputTransaksiToko::query()
            ->with(['akunTransaksi', 'user'])
            ->where('kategori_transaksi', KategoriAkun::Beban)
            ->whereBetween('tanggal_transaksi', [$start, $end])
            ->orderByDesc('nominal_transaksi');
    }
}
