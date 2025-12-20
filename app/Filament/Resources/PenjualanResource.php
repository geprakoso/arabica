<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use App\Filament\Resources\PenjualanResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PenjualanResource\RelationManagers\JasaRelationManager;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PenjualanResource extends Resource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Penjualan';

    protected static ?string $pluralLabel = 'Penjualan';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Detail Penjualan')
                    ->schema([
                        TextInput::make('no_nota')
                            ->label('No. Nota')
                            ->default(fn() => Penjualan::generateNoNota())
                            ->disabled()
                            ->prefixIcon('heroicon-s-tag')
                            ->unique(ignoreRecord: true)
                            ->required(),
                        DatePicker::make('tanggal_penjualan')
                            ->label('Tanggal Penjualan')
                            ->default(now())
                            ->prefixIcon('heroicon-s-calendar')
                            ->displayFormat('d F Y')
                            ->required()
                            ->native(false),
                        Select::make('id_karyawan')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false),
                        Select::make('id_member')
                            ->label('Member')
                            ->relationship('member', 'nama_member')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false),
                        RichEditor::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['items', 'jasaItems'])->withCount('items'))
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal_penjualan')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('member.nama_member')
                    ->label('Member')
                    ->placeholder('-')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->sortable(),
                TextColumn::make('grand_total_display')
                    ->label('Grand Total')
                    ->state(fn (Penjualan $record): string => self::formatCurrency(self::calculateGrandTotal($record))),
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
                            ->when($data['from'] ?? null, fn(Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '>=', $date))
                            ->when($data['until'] ?? null, fn(Builder $q, string $date) => $q->whereDate('tanggal_penjualan', '<=', $date));
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            JasaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjualans::route('/'),
            'create' => Pages\CreatePenjualan::route('/create'),
            'view' => Pages\ViewPenjualan::route('/{record}'),
            'edit' => Pages\EditPenjualan::route('/{record}/edit'),
        ];
    }

    /**
     * Mendapatkan pilihan batch yang tersedia untuk suatu produk, siap ditampilkan.
     *
     * Fungsi ini mencari item pembelian (PembelianItem) yang cocok dengan ID produk yang diberikan.
     * Hanya item dengan sisa stok positif yang akan diambil.
     * Setiap item kemudian diubah menjadi teks yang mudah dibaca, berisi nomor batch, sisa stok, dan HPP (Harga Pokok Penjualan).
     *
     * @param int|null $idProduk ID produk yang ingin dicari batch-nya.
     * @return array Daftar batch dalam bentuk array, di mana kunci adalah ID PembelianItem dan nilai adalah teks deskripsi batch.
     */
    public static function getBatchOptions(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $items = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->with('pembelian')
            ->orderBy($qtyColumn, 'desc')
            ->get()
            ->mapWithKeys(fn(PembelianItem $item) => [
                $item->id_pembelian_item => self::formatBatchLabel($item, $qtyColumn),
            ]);

        return $items->all();
    }

    /**
     * Membuat label batch untuk item pembelian.
     *
     * Mengambil data item pembelian lalu menghasilkan teks label yang mudah dibaca.
     * Label ini berisi nomor batch, jumlah sisa stok, dan HPP (harga pokok penjualan).
     *
     * @param \App\Models\PembelianItem|null $item Data item pembelian yang akan dibuat labelnya.
     * @param string $qtyColumn Nama kolom di database yang menyimpan jumlah sisa stok.
     * @return string|null Teks label batch yang sudah diformat, atau null jika item tidak ada.
     */
    public static function formatBatchLabel(?PembelianItem $item, string $qtyColumn): ?string
    {
        if (! $item) {
            return null;
        }

        //membuat label batch untuk item pembelian
        $labelParts = [
            $item->pembelian?->no_po ? '#' . $item->pembelian->no_po : 'Batch ' . $item->getKey(),
            'Qty: ' . number_format((int) ($item->{$qtyColumn} ?? 0), 0, ',', '.'),
            'HPP: Rp ' . number_format((int) ($item->hpp ?? 0), 0, ',', '.'),
        ];

        return implode(' | ', array_filter($labelParts));
    }

    /**
     * Mendapatkan daftar produk yang tersedia untuk dijual.
     *
     * Fungsi ini mencari produk-produk yang masih memiliki stok dari pembelian sebelumnya.
     * Hasilnya adalah daftar produk yang bisa dipilih saat melakukan penjualan.
     *
     * @return array Array berisi ID produk (sebagai kunci) dan nama produk (sebagai nilai).
     */
    public static function getAvailableProductOptions(): array
    {
        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return Produk::query()
            ->whereHas('pembelianItems', fn(Builder $query) => $query->where($qtyColumn, '>', 0))
            ->orderBy('nama_produk')
            ->pluck('nama_produk', 'id')
            ->all();
    }

    /**
     * Hitung grand total gabungan dari produk dan jasa.
     */
    protected static function calculateGrandTotal(Penjualan $record): float
    {
        $totalProduk = $record->items->sum(fn ($item) => (float) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0));
        $totalJasa = $record->jasaItems->sum(fn ($jasa) => (float) ($jasa->harga ?? 0) * (int) ($jasa->qty ?? 0));

        return $totalProduk + $totalJasa;
    }

    protected static function formatCurrency(float $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
