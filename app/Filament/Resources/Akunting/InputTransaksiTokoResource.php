<?php

namespace App\Filament\Resources\Akunting;

use App\Filament\Resources\Akunting\InputTransaksiTokoResource\Pages;
use App\Models\InputTransaksiToko;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\KategoriAkun; // Sesuaikan namespace Enum
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class InputTransaksiTokoResource extends Resource
{
    protected static ?string $model = InputTransaksiToko::class;
    protected static ?string $navigationLabel = 'Input Transaksi Toko';
    protected static ?string $pluralLabel = 'Input Transaksi Toko';
    protected static ?string $navigationIcon = 'hugeicons-wallet-add-01';
    protected static ?string $navigationGroup = 'Keuangan';

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->description('Input detail pemasukan atau pengeluaran kas.')
                    ->icon('heroicon-m-banknotes')
                    ->schema([
                        
                        // --- BARIS 1: Waktu & Klasifikasi ---
                        FormsGrid::make(2)
                            ->schema([
                                DatePicker::make('tanggal_transaksi')
                                    ->label('Tanggal')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->default(now())
                                    ->prefixIcon('hugeicons-calendar-01'),

                                Select::make('kategori_transaksi')
                                    ->label('Kategori')
                                    ->required()
                                    ->options(KategoriAkun::class)
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('hugeicons-tag-01')
                                    ->reactive()
                                    ->afterStateUpdated(fn (Set $set) => $set('kode_jenis_akun_id', null)),
                            ]),

                        // --- BARIS 2: Detail Akun ---
                        FormsGrid::make(2)
                            ->schema([
                                Select::make('kode_jenis_akun_id')
                                    ->label('Jenis Akun')
                                    ->required()
                                    ->relationship(
                                        name: 'jenisAkun',
                                        titleAttribute: 'nama_jenis_akun',
                                        modifyQueryUsing: fn (Builder $query, Get $get) => $get('kategori_transaksi')
                                            ? $query->whereHas('kodeAkun', fn (Builder $q) => $q->where('kategori_akun', $get('kategori_transaksi')))
                                            : $query->whereRaw('1 = 0'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('hugeicons-credit-card')
                                    ->placeholder('Pilih jenis akun')
                                    ->disabled(fn (Get $get) => blank($get('kategori_transaksi')))
                                    ->reactive(),

                                Select::make('akun_transaksi_id')
                                    ->label('Akun Transaksi (Opsional)')
                                    ->relationship('akunTransaksi', 'nama_akun')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('hugeicons-credit-card')
                                    ->placeholder('Pilih akun transaksi'),
                            ]),

                        // --- BARIS 3: Nominal & Keterangan ---
                        TextInput::make('nominal_transaksi')
                            ->label('Nominal (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                            ->stripCharacters([',', '.', 'Rp', ' '])
                            ->columnSpanFull(), // Nominal dibuat lebar agar fokus

                        Textarea::make('keterangan_transaksi')
                            ->label('Keterangan / Catatan')
                            ->placeholder('Contoh: Pembayaran listrik bulan November...')
                            ->rows(3)
                            ->columnSpanFull(),

                        // --- BARIS 4: Bukti ---
                        FileUpload::make('bukti_transaksi')
                            ->label('Upload Bukti')
                            ->directory('bukti-transaksi')
                            ->disk('public')
                            ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->maxSize(2048)
                            ->columnSpanFull(),

                        // --- Hidden Fields ---
                        Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Layout Grid 3 Kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Headline (Nominal & Waktu)
                        InfolistSection::make('Ringkasan Transaksi')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('tanggal_transaksi')
                                            ->label('Tanggal')
                                            ->date('d F Y')
                                            ->icon('heroicon-m-calendar'),

                                        TextEntry::make('kategori_transaksi')
                                            ->label('Kategori')
                                            ->badge()
                                            ->icon('heroicon-m-tag'),
                                    ]),

                                TextEntry::make('nominal_transaksi')
                                    ->label('Nominal')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntry\TextEntrySize::Large)
                                    // Warnai berdasarkan enum yang tersimpan; fallback ke abu-abu bila tidak dikenali.
                                    ->color(function ($state, $record) {
                                        /** @var KategoriAkun|null $kategori */
                                        $kategori = $record->kategori_transaksi instanceof KategoriAkun
                                            ? $record->kategori_transaksi
                                            : KategoriAkun::tryFrom($record->kategori_transaksi);

                                        return $kategori?->getColor() ?? 'gray';
                                    }),
                            ]),

                        // Section 2: Detail Akun & Keterangan
                        InfolistSection::make('Detail Akun')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('jenisAkun.nama_jenis_akun')
                                            ->label('Jenis Akun')
                                            ->icon('heroicon-m-credit-card')
                                            ->weight(FontWeight::Medium),

                                        TextEntry::make('akunTransaksi.nama_akun')
                                            ->label('Akun Transaksi')
                                            ->icon('heroicon-m-building-library')
                                            ->placeholder('-'),
                                    ]),

                                TextEntry::make('keterangan_transaksi')
                                    ->label('Keterangan')
                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                    ->markdown()
                                    ->columnSpanFull()
                                    ->placeholder('Tidak ada keterangan tambahan.'),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR - BUKTI) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Bukti Transaksi
                        // Gunakan komponen InfolistSection (bukan Forms Section) agar container sesuai tipe.
                        InfolistSection::make('Bukti Transaksi')
                            ->icon('heroicon-m-paper-clip')
                            ->schema([
                                ImageEntry::make('bukti_transaksi')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->height(250)
                                    // Hanya tampil jika ada file bukti.
                                    ->visible(fn ($record) => filled($record->bukti_transaksi))
                                    // Klik gambar buka versi penuh di tab baru (gunakan url bawaan komponen).
                                    ->url(fn ($record) => filled($record->bukti_transaksi) ? Storage::disk('public')->url($record->bukti_transaksi) : null, true)
                                    ->extraImgAttributes([
                                        'class' => 'object-contain rounded-lg border border-gray-200 w-full bg-gray-50',
                                        'alt' => 'Bukti Transaksi',
                                    ]),
                                // Tombol aksi: lihat penuh & unduh.
                                Actions::make([
                                    Action::make('view_full')
                                        ->label('Lihat')
                                        ->icon('heroicon-m-arrows-pointing-out')
                                        ->url(fn ($record) => filled($record->bukti_transaksi) ? Storage::disk('public')->url($record->bukti_transaksi) : null)
                                        ->openUrlInNewTab(),
                                    Action::make('download')
                                        ->label('Unduh')
                                        ->icon('heroicon-m-arrow-down-tray')
                                        // Pakai atribut download agar browser memaksa unduh file.
                                        ->url(fn ($record) => filled($record->bukti_transaksi) ? Storage::disk('public')->url($record->bukti_transaksi) : null)
                                        ->extraAttributes([
                                            'download' => true,
                                        ]),
                                ])->alignment('center'),
                            ]),

                        // Section 4: Metadata
                        InfolistSection::make('Log Input')
                            ->schema([
                                TextEntry::make('user.name') // Asumsi relasi user ada
                                    ->label('Dibuat Oleh')
                                    ->icon('heroicon-m-user-circle'),

                                TextEntry::make('created_at')
                                    ->label('Waktu Input')
                                    ->dateTime('d M Y, H:i')
                                    ->size(TextEntry\TextEntrySize::Small)
                                    ->color('gray'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenisAkun.nama_jenis_akun')
                    ->label('Jenis Akun')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori_transaksi')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?KategoriAkun $state) => $state?->getLabel() ?? '-')
                    ->color(fn (?KategoriAkun $state) => $state?->getColor()),
                Tables\Columns\TextColumn::make('nominal_transaksi')
                    ->label('Nominal')
                    ->money('idr', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Penginput')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInputTransaksiTokos::route('/'),
            'create' => Pages\CreateInputTransaksiToko::route('/create'),
            'view' => Pages\ViewInputTransaksiToko::route('/{record}'),
            'edit' => Pages\EditInputTransaksiToko::route('/{record}/edit'),
        ];
    }
}
