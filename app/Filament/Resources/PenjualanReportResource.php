<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use App\Filament\Resources\PenjualanReportResource\Pages;
use App\Models\Penjualan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenjualanReportResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $pluralLabel = 'Laporan Penjualan';

    protected static ?string $modelLabel = 'Laporan Penjualan';

    protected static ?string $pluralModelLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items', 'jasaItems', 'member', 'karyawan'])) // eager loading data relasi
            ->defaultSort('tanggal_penjualan', 'desc') // default sort
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->icon('heroicon-m-user-group')
                    ->weight('medium')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable(),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->badge()
                    ->color('info')
                    ->state(fn (Penjualan $record) => $record->items->sum('qty')) // menghitung total qty dari relasi items
                    ->sortable(),
                TextColumn::make('total_jasa')
                    ->label('Total Jasa')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateServiceTotal($record)
                    ))
                    ->color('warning')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->weight('bold')
                    ->color('success')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateProductTotal($record) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->sortable(),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        self::calculateHppTotal($record)
                    )) // format currency
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('total_margin')
                    ->label('Margin')
                    ->state(fn (Penjualan $record) => self::formatCurrency(
                        (self::calculateProductTotal($record) - self::calculateHppTotal($record)) + self::calculateServiceTotal($record)
                    )) // format currency
                    ->color('success')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->label('Periode')
                    ->form([
                        DatePicker::make('from')
                            ->native(false)
                            ->default(now()->subMonth())
                            ->label('Dari'),
                        DatePicker::make('until')
                            ->native(false)
                            ->default(now())
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('sumber_transaksi')
                    ->label('Sumber Transaksi')
                    ->options([
                        'pos' => 'POS',
                        'manual' => 'Manual',
                    ])
                    ->native(false)
                    ->placeholder('Semua'),
            ])
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Download')
                    ->defaultFormat('pdf')
                    ->filename('Laporan Penjualan'.'_'.date('d M Y'))
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->modalHeading(false)
                    ->extraViewData([
                        'title' => 'Haen Komputer',
                        'subtitle' => 'Laporan Penjualan',
                        'tanggal' => now()->format('d-m-Y'),
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualanReports::route('/'),
        ];
    }

    // format currency
    protected static function formatCurrency(int $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    protected static function calculateProductTotal(Penjualan $record): int
    {
        return (int) $record->items->sum(function ($item): int {
            $qty = (int) ($item->qty ?? 0);
            $harga = (int) ($item->harga_jual ?? 0);

            return $harga * $qty;
        });
    }

    protected static function calculateHppTotal(Penjualan $record): int
    {
        return (int) $record->items->sum(function ($item): int {
            $qty = (int) ($item->qty ?? 0);
            $hpp = (int) ($item->hpp ?? 0);

            return $hpp * $qty;
        });
    }

    protected static function calculateServiceTotal(Penjualan $record): int
    {
        return (int) $record->jasaItems->sum(function ($service): int {
            $qty = max(1, (int) ($service->qty ?? 1));
            $harga = (int) ($service->harga ?? 0);

            return $harga * $qty;
        });
    }
}
