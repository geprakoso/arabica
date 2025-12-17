<?php

namespace App\Filament\Resources;

// use App\Filament\Exports\PenjualanExporter;
use App\Filament\Resources\PenjualanReportResource\Pages;
use App\Models\Penjualan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
// use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenjualanReportResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $pluralLabel = 'Laporan Penjualan';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['items', 'member', 'karyawan'])) // reager loading data relasi 
            ->defaultSort('tanggal_penjualan', 'desc') // default sort
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->toggleable(),
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->state(fn(Penjualan $record) => $record->items->sum('qty')) //menghitung total qty dari relasi items
                    ->sortable(),
                TextColumn::make('total_penjualan')
                    ->label('Total Penjualan')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        $record->items->sum(fn($item) => (float) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0)) //menghitung total penjualan dari relasi items
                    )) // format currency
                    ->sortable(),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        $record->items->sum(fn($item) => (float) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))
                    )) // format currency
                    ->sortable(),
                TextColumn::make('total_margin')
                    ->label('Margin')
                    ->state(fn(Penjualan $record) => self::formatCurrency(
                        $record->items->sum(fn($item) => ((float) ($item->harga_jual ?? 0) - (float) ($item->hpp ?? 0)) * (int) ($item->qty ?? 0))
                    )) // format currency
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->label('Periode')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn(Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '<=', $date));
                    }),
            ])
            // ->headerActions([
            //     ExportAction::make('export_penjualan')
            //         ->label('Download CSV')
            //         ->color('primary')
            //         ->exporter(PenjualanExporter::class),
            // ])
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
    protected static function formatCurrency(float | int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
