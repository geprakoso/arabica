<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use App\Filament\Resources\PenjualanResource\RelationManagers\ItemsRelationManager;
use App\Models\PembelianItem;
use App\Models\Member;
use App\Models\Penjualan;
use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

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
                            ->unique(ignoreRecord: true)
                            ->required(),
                        DatePicker::make('tanggal_penjualan')
                            ->label('Tanggal Penjualan')
                            ->required()
                            ->default(now())
                            ->native(false),
                        Select::make('id_karyawan')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()->karyawan?->id)
                            ->required()
                            ->native(false),
                        Select::make('id_member')
                            ->label('Member')
                            ->relationship('member', 'nama_member')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false)
                            ->createOptionModalHeading('Tambah Member')
                            ->createOptionAction(fn ($action) => $action->label('Tambah Member'))
                            ->createOptionForm([
                                TextInput::make('nama_member')
                                    ->label('Nama Lengkap')
                                    ->required(),

                                Grid::make(2)->schema([
                                    TextInput::make('no_hp')
                                        ->label('Nomor WhatsApp / HP')
                                        ->tel()
                                        ->required()
                                        ->unique(table: (new Member())->getTable(), column: 'no_hp'),

                                    TextInput::make('email')
                                        ->label('Alamat Email')
                                        ->email()
                                        ->nullable(),
                                ]),

                                Textarea::make('alamat')
                                    ->label('Alamat')
                                    ->rows(3)
                                    ->nullable(),

                                Grid::make(3)->schema([
                                    TextInput::make('provinsi')->label('Provinsi')->nullable(),
                                    TextInput::make('kota')->label('Kota/Kabupaten')->nullable(),
                                    TextInput::make('kecamatan')->label('Kecamatan')->nullable(),
                                ]),
                            ]),
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
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['items'])->withCount('items'))
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
                TextColumn::make('total_qty')
                    ->label('Total Qty')
                    ->state(fn(Penjualan $record) => $record->items->sum('qty'))
                    ->sortable(),
            ])
            ->filters([])
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // === BAGIAN ATAS: HEADER DOKUMEN ===
                InfoSection::make()
                    ->schema([
                        Split::make([
                            // Kiri: Identitas Nota
                            InfoGroup::make([
                                TextEntry::make('no_nota')
                                    ->label('No. Nota')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-document-text'),

                                TextEntry::make('tanggal_penjualan')
                                    ->label('Tanggal Penjualan')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                            ]),

                            // Tengah: Member & Karyawan
                            InfoGroup::make([
                                TextEntry::make('member.nama_member')
                                    ->label('Member')
                                    ->icon('heroicon-m-user-group')
                                    ->color('primary')
                                    ->placeholder('-'),

                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Kasir / Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                            ]),

                            // Kanan: Pembayaran (opsional)
                            InfoGroup::make([
                                TextEntry::make('metode_bayar')
                                    ->label('Metode Bayar')
                                    ->badge()
                                    ->placeholder('-')
                                    ->formatStateUsing(function ($state): ?string {
                                        if (! $state) {
                                            return null;
                                        }

                                        return method_exists($state, 'label') ? $state->label() : (string) $state;
                                    })
                                    ->color(function ($state): string {
                                        $value = method_exists($state, 'value') ? $state->value : $state;

                                        return match ($value) {
                                            'cash' => 'success',
                                            'transfer' => 'info',
                                            'card' => 'warning',
                                            'ewallet' => 'primary',
                                            default => 'gray',
                                        };
                                    }),

                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->money('IDR')
                                    ->state(function (Penjualan $record): float {
                                        $subtotal = (float) ($record->items()
                                            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
                                            ->value('total') ?? 0);

                                        return max(0, $subtotal - (float) ($record->diskon_total ?? 0));
                                    })
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->placeholder('-'),
                            ])->grow(false),
                        ])->from('md'),
                    ]),

                // === BAGIAN TENGAH: TABEL BARANG (TABLE) ===
                InfoSection::make('Daftar Barang')
                    // ->compact()
                    ->schema([
                        ViewEntry::make('items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.penjualan-items-table')
                            ->state(fn (Penjualan $record) => $record->items()->with(['produk', 'pembelianItem.pembelian'])->get()),
                    ]),

                // === BAGIAN BAWAH: CATATAN & RINGKASAN ===
                InfoSection::make()
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('catatan')
                                    ->label('Catatan')
                                    ->markdown()
                                    ->prose()
                                    ->placeholder('Tidak ada catatan'),
                            ]),

                            InfoGroup::make([
                                TextEntry::make('total')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->state(fn (Penjualan $record): float => (float) ($record->items()
                                        ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
                                        ->value('total') ?? 0))
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-'),

                                TextEntry::make('diskon_total')
                                    ->label('Diskon')
                                    ->money('IDR')
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-'),

                                TextEntry::make('tunai_diterima')
                                    ->label('Tunai Diterima')
                                    ->money('IDR')
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-')
                                    ->visible(fn (Penjualan $record) => (string) ($record->metode_bayar?->value ?? $record->metode_bayar ?? '') === 'cash'),

                                TextEntry::make('kembalian')
                                    ->label('Kembalian')
                                    ->money('IDR')
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-')
                                    ->visible(fn (Penjualan $record) => (string) ($record->metode_bayar?->value ?? $record->metode_bayar ?? '') === 'cash'),
                            ])->grow(false),
                        ])->from('md'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
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
}
