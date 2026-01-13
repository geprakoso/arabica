<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Member;
use App\Models\Produk;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Penjualan;
use Filament\Tables\Table;
use App\Models\PembelianItem;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use App\Filament\Resources\PenjualanResource\Pages;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use App\Filament\Resources\PenjualanResource\RelationManagers\JasaRelationManager;
use App\Filament\Resources\PenjualanResource\RelationManagers\ItemsRelationManager;

class PenjualanResource extends BaseResource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Input Penjualan';

    protected static ?string $pluralLabel = 'Input Penjualan';

    protected static ?int $navigationSort = 4;

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
                            ->default(now())
                            ->native(false),
                        Select::make('id_karyawan')
                            ->label('Karyawan')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->default(fn() => Auth::user()->karyawan?->id)
                            ->required()
                            ->native(false),
                        Select::make('id_member')
                            ->label('Member')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false)
                            ->createOptionModalHeading('Tambah Member')
                            ->createOptionAction(fn($action) => $action->label('Tambah Member'))
                            ->createOptionForm([
                                TextInput::make('nama_member')
                                    ->label('Nama Lengkap')
                                    ->required(),

                                Grid::make(2)->schema([
                                    TextInput::make('no_hp')
                                        ->label('Nomor WhatsApp / HP')
                                        ->tel()
                                        ->required()
                                        ->unique(table: (new Member)->getTable(), column: 'no_hp'),

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
                        TextInput::make('diskon_total')
                            ->label('Diskon')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        TableRepeater::make('pembayaran')
                            ->label('Pembayaran (Split)')
                            ->relationship('pembayaran')
                            ->minItems(0)
                            ->addActionLabel('Tambah Pembayaran')
                            ->colStyles([
                                'metode_bayar' => 'width: 20%;',
                                'akun_transaksi_id' => 'width: 30%;',
                                'jumlah' => 'width: 50%;',
                            ])
                            ->childComponents([
                                Select::make('metode_bayar')
                                    ->label('Metode')
                                    ->options([
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                    ])
                                    ->native(false)
                                    ->required()
                                    ->reactive(),
                                Select::make('akun_transaksi_id')
                                    ->label('Akun Transaksi')
                                    ->relationship('akunTransaksi', 'nama_akun', fn(Builder $query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required(fn(Get $get) => $get('metode_bayar') === 'transfer'),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required(),
                            ])
                            ->columns(4),
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
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->with(['items', 'jasaItems'])
                ->withCount(['items', 'jasaItems'])
                ->withSum('pembayaran', 'jumlah'))
            ->columns([
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-receipt-percent')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable()
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
                    ->placeholder('-')
                    ->weight('medium')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('secondary')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Item & Jasa')
                    ->badge()
                    ->toggleable()
                    ->icon('heroicon-m-shopping-cart')
                    ->color('primary')
                    ->alignCenter()
                    ->sortable(),
                TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->copyable()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);
                        $sisa = max(0, $grandTotal - $totalPaid);

                        return $sisa > 0 ? 'Belum Lunas' : 'Lunas';
                    })
                    ->color(fn(string $state): string => $state === 'Lunas' ? 'success' : 'danger')
                    ->alignCenter(),
                TextColumn::make('sisa_bayar_display')
                    ->label('Sisa Bayar')
                    ->alignRight()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);

                        $sisa = max(0, $grandTotal - $totalPaid);

                        return self::formatCurrency((int) $sisa);
                    })
                    ->copyable(),
                TextColumn::make('grand_total_display')
                    ->label('Grand Total')
                    ->weight('bold')
                    ->color('success')
                    ->alignRight()
                    ->state(fn(Penjualan $record): string => self::formatCurrency(self::calculateGrandTotal($record))),
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
                Action::make('invoice')
                    ->label('Invoice')
                    ->icon('heroicon-m-printer')
                    ->color('primary')
                    ->url(fn(Penjualan $record) => route('penjualan.invoice', $record))
                    ->openUrlInNewTab(),
                Action::make('invoice_simple')
                    ->label('Invoice Simple')
                    ->icon('heroicon-m-document-text')
                    ->color('gray')
                    ->url(fn(Penjualan $record) => route('penjualan.invoice.simple', $record))
                    ->openUrlInNewTab(),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->tooltip('Lihat Detail'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->tooltip('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash'),
                ])
                    ->hidden(function (Penjualan $record): bool {
                        $hasLines = $record->items()->exists() || $record->jasaItems()->exists();
                        $grandTotal = (float) ($record->grand_total ?? 0);
                        $totalPaid = (float) ($record->pembayaran_sum_jumlah ?? 0);
                        $isUnpaid = $totalPaid < $grandTotal;

                        if ($isUnpaid || $grandTotal <= 0) {
                            return false;
                        }

                        return $hasLines && $grandTotal > 0;
                    })
                    ->label('Aksi')
                    ->tooltip('Aksi'),
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

                                TextEntry::make('tukar_tambah_link')
                                    ->label('Tukar Tambah')
                                    ->state(fn(Penjualan $record): ?string => $record->tukarTambah?->kode)
                                    ->icon('heroicon-m-arrows-right-left')
                                    ->url(fn(Penjualan $record) => $record->tukarTambah
                                        ? TukarTambahResource::getUrl('view', ['record' => $record->tukarTambah])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                            ]),

                            // Kanan: Pembayaran (opsional)
                            InfoGroup::make([
                                TextEntry::make('metode_bayar')
                                    ->label('Metode Bayar')
                                    ->badge()
                                    ->placeholder('-')
                                    ->state(function (Penjualan $record): ?string {
                                        $methods = $record->pembayaran
                                            ? $record->pembayaran->pluck('metode_bayar')->filter()->map('strval')->unique()->values()
                                            : collect();

                                        if ($methods->isNotEmpty()) {
                                            $labels = $methods->map(function (string $method): string {
                                                return match ($method) {
                                                    'cash' => 'Tunai',
                                                    'transfer' => 'Transfer',
                                                    default => strtoupper($method),
                                                };
                                            });

                                            return $labels->implode(' + ');
                                        }

                                        $state = $record->metode_bayar;
                                        if (! $state) {
                                            return null;
                                        }

                                        return method_exists($state, 'label') ? $state->label() : (string) $state;
                                    })
                                    ->color('primary'),

                                TextEntry::make('grand_total')
                                    ->label('Grand Total')
                                    ->money('IDR')
                                    ->state(function (Penjualan $record): float {
                                        $subtotalProduk = (float) ($record->items()
                                            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
                                            ->value('total') ?? 0);
                                        $subtotalJasa = (float) ($record->jasaItems()
                                            ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
                                            ->value('total') ?? 0);

                                        return max(0, ($subtotalProduk + $subtotalJasa) - (float) ($record->diskon_total ?? 0));
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
                            ->state(fn(Penjualan $record) => $record->items()->with(['produk', 'pembelianItem.pembelian'])->get()),
                    ]),

                InfoSection::make('Daftar Jasa')
                    ->schema([
                        ViewEntry::make('jasa_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.penjualan-jasa-table')
                            ->state(fn(Penjualan $record) => $record->jasaItems()->with('jasa')->get()),
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
                                TextEntry::make('total_dibayar')
                                    ->label('Total Dibayar')
                                    ->money('IDR')
                                    ->state(function (Penjualan $record): float {
                                        return (float) ($record->pembayaran()->sum('jumlah') ?? 0);
                                    })
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-'),
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Bayar')
                                    ->money('IDR')
                                    ->state(function (Penjualan $record): float {
                                        $subtotalProduk = (float) ($record->items()
                                            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
                                            ->value('total') ?? 0);
                                        $subtotalJasa = (float) ($record->jasaItems()
                                            ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
                                            ->value('total') ?? 0);
                                        $diskon = (float) ($record->diskon_total ?? 0);
                                        $grandTotal = max(0, ($subtotalProduk + $subtotalJasa) - $diskon);

                                        $totalPaid = (float) ($record->pembayaran()->sum('jumlah') ?? 0);

                                        return max(0, $grandTotal - $totalPaid);
                                    })
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-'),
                                TextEntry::make('total')
                                    ->label('Subtotal')
                                    ->money('IDR')
                                    ->state(function (Penjualan $record): float {
                                        $subtotalProduk = (float) ($record->items()
                                            ->selectRaw('COALESCE(SUM(qty * harga_jual), 0) as total')
                                            ->value('total') ?? 0);
                                        $subtotalJasa = (float) ($record->jasaItems()
                                            ->selectRaw('COALESCE(SUM(qty * harga), 0) as total')
                                            ->value('total') ?? 0);

                                        return $subtotalProduk + $subtotalJasa;
                                    })
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
                                    ->visible(fn(Penjualan $record) => (string) ($record->metode_bayar?->value ?? $record->metode_bayar ?? '') === 'cash'),

                                TextEntry::make('kembalian')
                                    ->label('Kembalian')
                                    ->money('IDR')
                                    ->extraAttributes([
                                        'class' => '[&_.fi-in-affixes_.min-w-0>div]:justify-start [&_.fi-in-affixes_.min-w-0>div]:text-left md:[&_.fi-in-affixes_.min-w-0>div]:justify-end md:[&_.fi-in-affixes_.min-w-0>div]:text-right',
                                    ])
                                    ->placeholder('-')
                                    ->visible(fn(Penjualan $record) => (string) ($record->metode_bayar?->value ?? $record->metode_bayar ?? '') === 'cash'),
                            ])->grow(false),
                        ])->from('md'),
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
     * @param  int|null  $idProduk  ID produk yang ingin dicari batch-nya.
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
     * @param  \App\Models\PembelianItem|null  $item  Data item pembelian yang akan dibuat labelnya.
     * @param  string  $qtyColumn  Nama kolom di database yang menyimpan jumlah sisa stok.
     * @return string|null Teks label batch yang sudah diformat, atau null jika item tidak ada.
     */
    public static function formatBatchLabel(?PembelianItem $item, string $qtyColumn): ?string
    {
        if (! $item) {
            return null;
        }

        // membuat label batch untuk item pembelian
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
     * Hitung grand total gabungan dari produk dan jasa setelah diskon.
     */
    protected static function calculateGrandTotal(Penjualan $record): int
    {
        $totalProduk = $record->items->sum(fn($item) => (int) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0));
        $totalJasa = $record->jasaItems->sum(fn($jasa) => (int) ($jasa->harga ?? 0) * (int) ($jasa->qty ?? 0));
        $diskon = (int) ($record->diskon_total ?? 0);

        return max(0, ($totalProduk + $totalJasa) - $diskon);
    }

    protected static function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
