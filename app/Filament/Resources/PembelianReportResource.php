<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PembelianExporter;
use App\Filament\Resources\PembelianReportResource\Pages;
use App\Models\Pembelian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PembelianReportResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Laporan Pembelian';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['supplier', 'karyawan', 'items']))
            ->columns([
                TextColumn::make('no_po')
                    ->label('No. PO')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('supplier.nama_supplier')
                    ->label('Supplier')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->toggleable(),
                TextColumn::make('total_items')
                    ->label('Total Qty')
                    ->state(fn (Pembelian $record) => $record->items->sum('qty'))
                    ->sortable(),
                TextColumn::make('total_hpp')
                    ->label('Total HPP')
                    ->state(fn (Pembelian $record) => self::formatCurrency(
                        $record->items->sum(fn ($item) => (float) ($item->hpp ?? 0) * (int) ($item->qty ?? 0))
                    ))
                    ->sortable(),
                TextColumn::make('total_harga_jual')
                    ->label('Total Harga Jual')
                    ->state(fn (Pembelian $record) => self::formatCurrency(
                        $record->items->sum(fn ($item) => (float) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0))
                    ))
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
                            ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->whereDate('tanggal', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->whereDate('tanggal', '<=', $date));
                    }),
            ])
            ->headerActions([
                ExportAction::make('export_pembelian')
                    ->label('Download CSV')
                    ->color('primary')
                    ->exporter(PembelianExporter::class),
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
            'index' => Pages\ListPembelianReports::route('/'),
        ];
    }

    protected static function formatCurrency(float | int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
