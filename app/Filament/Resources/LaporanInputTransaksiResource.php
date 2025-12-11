<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\KategoriAkun;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ExportAction;
use App\Models\InputTransaksiToko;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\LaporanInputTransaksiResource\Pages;

class LaporanInputTransaksiResource extends Resource
{
    // Kita gunakan Model yang sama, tapi Resource ini khusus Laporan
    protected static ?string $model = InputTransaksiToko::class;

    // Ganti Icon agar terlihat seperti report
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Laporan Keuangan';
    
    protected static ?string $pluralModelLabel = 'Laporan Keuangan';

    protected static ?string $slug = 'laporan-keuangan';

    // Urutkan menu di bawah menu input
    protected static ?int $navigationSort = 2;

    // Matikan fitur Tambah Data (Create)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('kategori_transaksi')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()) // Mengambil warna dari Enum
                    ->sortable(),

                TextColumn::make('jenisAkun.nama_jenis_akun')
                    ->label('Akun')
                    ->searchable(),

                TextColumn::make('keterangan_transaksi')
                    ->label('Ket.')
                    ->limit(30)
                    ->tooltip(fn ($state) => $state),

                // INI BAGIAN TERPENTING: SUMMARIZER
                TextColumn::make('nominal_transaksi')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    // Menambahkan ringkasan total di bawah tabel
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->money('IDR')
                    ]),
            ])
            // Filter sangat krusial untuk laporan
            ->filters([
                // Filter Rentang Tanggal
                Filter::make('tanggal_transaksi')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()), // Default awal bulan
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->default(now()), // Default hari ini
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date) => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date) => $query->whereDate('tanggal_transaksi', '<=', $date),
                            );
                    })
                    ->columns(2), // Tampilan filter berdampingan

                // Filter Kategori (Pemasukan/Pengeluaran)
                SelectFilter::make('kategori_transaksi')
                    ->label('Kategori')
                    ->options(KategoriAkun::class),
                
            ], layout: FiltersLayout::AboveContent) // Letakkan filter di atas tabel agar terlihat jelas
            
            // Tambahkan Export data (Fitur bawaan Filament v3)
            ->headerActions([
                // Pastikan kamu sudah jalankan: php artisan filament:install --scaffold (jika perlu export class)
                // Atau gunakan simple export action jika versi filament terbaru support
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\InputTransaksiTokoExporter::class) 
                    // *Catatan: Kamu perlu buat Exporter dulu, lihat instruksi di bawah kode ini.
                    ->label('Export Excel'),
            ])
            ->actions([
                // Hanya tombol View, karena ini laporan
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]); // Matikan bulk delete agar data aman
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanInputTransaksis::route('/'),
        ];
    }
}
