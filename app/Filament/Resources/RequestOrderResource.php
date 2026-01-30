<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestOrderResource\Pages;
use App\Models\RequestOrder;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split as FormsSplit;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class RequestOrderResource extends BaseResource
{
    protected static ?string $model = RequestOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $recordTitleAttribute = 'no_ro';

    protected static ?string $pluralLabel = 'Request Order';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_ro', 'karyawan.nama_karyawan'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === BAGIAN ATAS: DETAIL PERMINTAAN & CATATAN ===
                FormsSplit::make([
                    // Kolom Kiri: Detail Permintaan
                    Section::make('Informasi Utama')
                        ->icon('heroicon-o-document-text')
                        ->description('Detail permintaan barang baru')
                        ->icon('heroicon-m-document-text')
                        ->schema([
                            TextInput::make('no_ro')
                                ->label('Nomor RO')
                                ->prefixIcon('heroicon-o-hashtag')
                                ->required()
                                ->default(fn() => RequestOrder::generateRO())
                                ->disabled()
                                ->dehydrated()
                                ->unique(ignoreRecord: true),

                            Select::make('karyawan_id')
                                ->label('Karyawan Pemohon')
                                ->prefixIcon('heroicon-o-user')
                                ->relationship('karyawan', 'nama_karyawan')
                                ->searchable()
                                ->preload()
                                ->default(fn(Get $get) => auth()->user()->karyawan?->id)
                                ->native(false)
                                ->placeholder('Pilih Nama Karyawan')
                                ->required(),

                            DatePicker::make('tanggal')
                                ->label('Tanggal Permintaan Permintaan')
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->displayFormat('d F Y')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->default(now()),
                        ]),

                    // Kolom Kanan: Catatan
                    Section::make('Keterangan Tambahan')
                        ->icon('heroicon-o-pencil')
                        ->description('Catatan opsional untuk permintaan ini Tambahan')
                        ->icon('heroicon-m-pencil-square')
                        ->schema([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan')
                                ->rows(5)
                                ->placeholder('Tuliskan alasan atau catatan penting lainnya...')
                                ->columnSpanFull()
                                ->nullable(),
                        ]),
                ])
                    ->columnSpanFull(),

                Section::make('Daftar Item')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Masukkan daftar barang yang ingin diminta')
                    ->schema([
                        TableRepeater::make('items')
                            ->label('')
                            ->relationship('items')
                            ->minItems(1)
                            ->addActionLabel('Tambah Produk Lain')
                            ->schema([
                                Forms\Components\Select::make('kategori_id')
                                    ->label('Kategori')
                                    ->prefixIcon('heroicon-o-tag')
                                    ->options(fn() => \App\Models\Kategori::query()->orderBy('nama_kategori')->pluck('nama_kategori', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->reactive()
                                    ->afterStateHydrated(function ($state, callable $set, Get $get): void {
                                        if ($state) {
                                            return;
                                        }

                                        $produkId = $get('produk_id');
                                        if (! $produkId) {
                                            return;
                                        }

                                        $produk = \App\Models\Produk::find($produkId);
                                        $set('kategori_id', $produk?->kategori_id);
                                    })
                                    ->afterStateUpdated(fn(callable $set) => $set('produk_id', null))
                                    ->dehydrated(false),
                                Forms\Components\Select::make('produk_id')
                                    ->label('Nama Produk')
                                    ->prefixIcon('heroicon-o-cube')
                                    ->options(fn(Get $get) => $get('kategori_id')
                                        ? \App\Models\Produk::query()
                                            ->where('kategori_id', $get('kategori_id'))
                                            ->orderBy('nama_produk')
                                            ->get()
                                            ->mapWithKeys(fn ($produk) => [$produk->id => strtoupper($produk->nama_produk)])
                                            ->all()
                                        : [])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->placeholder('Cari Produk...')
                                    ->disabled(fn(Get $get) => blank($get('kategori_id')))
                                    ->reactive(),
                            ])
                            ->reorderable(true)
                            ->columns(2)
                            ->cloneable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_ro')
                    ->label('No. RO')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-hashtag')
                    ->color('primary')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Pemohon')
                    ->icon('heroicon-m-user-circle')
                    ->weight('medium')
                    ->color('success')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Jml Item')
                    ->counts('items')
                    ->badge()
                    ->icon('heroicon-m-shopping-cart')
                    ->color('info')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('karyawan_id')
                    ->label('Filter Karyawan')
                    ->native(false)
                    ->relationship('karyawan', 'nama_karyawan'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('primary'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\DeleteAction::make(),
                ])
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
                InfoSection::make('Informasi Utama')
                    ->schema([
                        TextEntry::make('no_ro')
                            ->label('No. RO')
                            ->icon('heroicon-m-hashtag'),
                        TextEntry::make('tanggal')
                            ->label('Tanggal')
                            ->date('d F Y')
                            ->icon('heroicon-m-calendar-days'),
                        TextEntry::make('karyawan.nama_karyawan')
                            ->label('Pemohon')
                            ->icon('heroicon-m-user')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                InfoSection::make('Daftar Item')
                    ->schema([
                        ViewEntry::make('items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.request-order-items-table')
                            ->state(fn(RequestOrder $record) => $record->items),
                    ]),
                InfoSection::make('Catatan')
                    ->visible(fn(RequestOrder $record) => filled($record->catatan))
                    ->schema([
                        TextEntry::make('catatan')
                            ->hiddenLabel()
                            ->markdown(),
                    ]),
                InfoSection::make('Foto Dokumentasi')
                    ->icon('heroicon-o-camera')
                    ->visible(fn(RequestOrder $record) => ! empty($record->foto_dokumen))
                    ->schema([
                        ViewEntry::make('foto_dokumen')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.foto-dokumen-gallery')
                            ->state(fn(RequestOrder $record) => $record->foto_dokumen ?? []),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestOrders::route('/'),
            'create' => Pages\CreateRequestOrder::route('/create'),
            'view' => Pages\ViewRequestOrder::route('/{record}'),
            'edit' => Pages\EditRequestOrder::route('/{record}/edit'),
        ];
    }
}
