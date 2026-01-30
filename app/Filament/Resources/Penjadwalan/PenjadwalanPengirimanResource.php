<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Penjadwalan\PenjadwalanPengirimanResource\Pages;
use App\Models\Member;
use App\Models\PenjadwalanPengiriman;
use App\Models\Penjualan;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\Group as FormsGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PenjadwalanPengirimanResource extends BaseResource
{
    protected static ?string $model = PenjadwalanPengiriman::class;

    protected static ?string $navigationIcon = 'hugeicons-shipping-truck-01';

    protected static ?string $navigationLabel = 'Pengiriman';

    protected static ?string $navigationGroup = 'Tugas';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'no_resi';

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_resi', 'penjualan.no_nota', 'member.nama_member', 'driver.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Pelanggan' => $record->member->nama_member,
            'Tujuan' => $record->alamat,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                FormsGrid::make(3)
                    ->schema([
                        // --- KOLOM KIRI (DATA PENGIRIMAN & TUJUAN) ---
                        FormsGroup::make()
                            ->schema([
                                // Section 1: Sumber & Tujuan
                                FormsSection::make('Informasi Tujuan')
                                    ->description('Detail lokasi dan penerima paket.')
                                    ->icon('heroicon-m-map-pin')
                                    ->schema([
                                        // Baris 1: Referensi Nota
                                        Select::make('penjualan_id')
                                            ->label('No. Nota Penjualan')
                                            ->relationship('penjualan', 'no_nota')
                                            ->searchable()
                                            ->preload()
                                            ->live() // Live update agar field di bawahnya bisa terisi otomatis
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                // Saat Nota dipilih, otomatis isi member, no HP, dan alamat dari Penjualan
                                                $penjualan = Penjualan::with('member')->find($state);

                                                if (! $penjualan) {
                                                    return;
                                                }

                                                $set('member_id', $penjualan->id_member);
                                                $set('penerima_no_hp', $penjualan->member?->no_hp);
                                                $set('alamat', $penjualan->member?->alamat);
                                            })
                                            ->columnSpanFull(),

                                        // Baris 2: Data Penerima
                                        Select::make('member_id')
                                            ->label('Pelanggan (Pemesan)')
                                            ->relationship('member', 'nama_member')
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (! $state) {
                                                    return;
                                                }

                                                $member = Member::find($state);
                                                $set('penerima_no_hp', $member?->no_hp);
                                            }),

                                        TextInput::make('penerima_no_hp')
                                            ->label('No. HP Penerima')
                                            ->tel()
                                            ->required(),

                                        // Baris 3: Alamat Lengkap
                                        Textarea::make('alamat')
                                            ->label('Alamat Pengiriman')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull()
                                            ->hintAction(
                                                Action::make('bukaMaps')
                                                    ->icon('heroicon-m-map')
                                                    ->url(fn ($get) => 'https://www.google.com/maps/search/?api=1&query='.urlencode($get('alamat')), true)
                                                    ->label('Cek Google Maps')
                                            ),

                                        Textarea::make('catatan_kurir')
                                            ->label('Patokan / Catatan Kurir')
                                            ->placeholder('Contoh: Rumah pagar hitam, titip di satpam.')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                // Section 2: Manifest (Barang) - Opsional jika ingin detail
                                FormsSection::make('Manifest Barang')
                                    ->collapsed() // Bisa di-collapse agar form tidak kepanjangan
                                    ->schema([
                                        Placeholder::make('manifest_barang')
                                            ->label('Daftar Barang')
                                            ->content(function (callable $get) {
                                                $penjualan = Penjualan::with('items.produk')
                                                    ->find($get('penjualan_id'));

                                                if (! $penjualan || $penjualan->items->isEmpty()) {
                                                    return 'Pilih nota penjualan untuk melihat daftar barang.';
                                                }

                                                return $penjualan->items
                                                    ->map(fn ($item) => ($item->produk?->nama_produk ?? '-').' x '.$item->qty)
                                                    ->implode("\n");
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // --- KOLOM KANAN (LOGISTIK & STATUS) ---
                        FormsGroup::make()
                            ->schema([
                                FormsSection::make('Logistik')
                                    ->icon('heroicon-m-truck')
                                    ->schema([
                                        TextInput::make('no_resi')
                                            ->label('No. Surat Jalan / Resi')
                                            ->default(fn () => 'DEL-'.now()->format('ymd').'-'.rand(1000, 9999))
                                            ->disabled()
                                            ->dehydrated()
                                            ->required(),

                                        Select::make('karyawan_id')
                                            ->label('Driver / Kurir')
                                            ->relationship('driver', 'name') // Relasi ke User
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        DatePicker::make('tanggal_penerimaan')
                                            ->label('Tanggal Kirim')
                                            ->default(now())
                                            ->native(false)
                                            ->required(),

                                        Select::make('status')
                                            ->options([
                                                'pending' => 'Menunggu Jadwal',
                                                'loading' => 'Muat Barang (Loading)',
                                                'shipping' => 'Dalam Perjalanan',
                                                'delivered' => 'Terkirim / Selesai',
                                                'failed' => 'Gagal / Retur',
                                            ])
                                            ->default('pending')
                                            ->selectablePlaceholder(false)
                                            ->native(false)
                                            ->required(),
                                    ]),

                                // Section Upload Bukti (Hanya muncul jika status delivered)
                                FormsSection::make('Bukti Serah Terima')
                                    ->schema([
                                        FileUpload::make('bukti_foto')
                                            ->label('Foto Penerima / Lokasi')
                                            ->image()
                                            ->directory('delivery-proofs')
                                            ->imageEditor(),

                                        TextInput::make('penerima_nama_asli')
                                            ->label('Diterima Oleh (Nama Jelas)')
                                            ->placeholder('Nama orang yang menerima'),
                                    ])
                                    ->visible(fn ($get) => $get('status') === 'delivered'),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistGrid::make(3)
                    ->schema([
                        // --- KOLOM KIRI (TUJUAN & BARANG) ---
                        InfolistGroup::make()
                            ->schema([
                                // Section 1: Alamat & Penerima (Highlight Utama)
                                InfolistSection::make('Tujuan Pengiriman')
                                    ->icon('heroicon-m-map-pin')
                                    ->schema([
                                        // Baris 1: Alamat Besar
                                        TextEntry::make('alamat')
                                            ->hiddenLabel() // Sembunyikan label biar alamatnya langsung terlihat besar
                                            ->weight(FontWeight::Medium)
                                            ->size(TextEntrySize::Large)
                                            ->icon('heroicon-m-home')
                                            ->iconPosition(IconPosition::Before)
                                            ->url(fn ($record) => 'https://www.google.com/maps/search/?api=1&query='.urlencode($record->alamat), true) // Klik lari ke Gmaps
                                            ->tooltip('Klik untuk buka Google Maps')
                                            ->color('primary') // Warna biru biar terlihat seperti link
                                            ->columnSpanFull(),

                                        // Baris 2: Catatan Kurir
                                        TextEntry::make('catatan')
                                            ->label('Patokan / Catatan')
                                            ->icon('heroicon-m-information-circle')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'italic'])
                                            ->columnSpanFull(),

                                        // Baris 3: Kontak Penerima
                                        InfolistGrid::make(2)
                                            ->schema([
                                                TextEntry::make('member.nama_member')
                                                    ->label('Nama Penerima')
                                                    ->icon('heroicon-m-user'),

                                                TextEntry::make('penerima_no_hp')
                                                    ->label('Kontak (HP)')
                                                    ->icon('heroicon-m-phone')
                                                    ->copyable()
                                                    ->url(fn ($record) => 'https://wa.me/'.$record->penerima_no_hp, true)
                                                    ->color('success'),
                                            ]),
                                    ]),

                                // Section 2: Informasi Sumber Barang
                                InfolistSection::make('Manifest Barang')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        TextEntry::make('penjualan.no_nota') // Asumsi relasi ke Penjualan
                                            ->label('Sumber Nota')
                                            ->badge()
                                            ->color('gray')
                                            ->copyable(),

                                        RepeatableEntry::make('penjualan.items')
                                            ->label('Daftar Barang')
                                            ->columns(3)
                                            ->schema([
                                                TextEntry::make('produk.nama_produk')
                                                    ->label('Produk')
                                                    ->weight(FontWeight::Medium),
                                                TextEntry::make('qty')
                                                    ->label('Qty'),
                                                TextEntry::make('harga_jual')
                                                    ->label('Harga')
                                                    ->money('IDR', true),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // --- KOLOM KANAN (LOGISTIK & STATUS) ---
                        InfolistGroup::make()
                            ->schema([
                                // Card Status (Paling Atas)
                                InfolistSection::make('Status Pengiriman')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('no_resi')
                                            ->label('No. Surat Jalan')
                                            ->fontFamily(FontFamily::Mono)
                                            ->weight(FontWeight::Bold)
                                            ->copyable()
                                            ->icon('heroicon-m-qr-code'),

                                        TextEntry::make('status')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                'pending' => 'Menunggu Jadwal',
                                                'loading' => 'Sedang Muat (Loading)',
                                                'shipping' => 'Dalam Perjalanan',
                                                'delivered' => 'Terkirim',
                                                'failed' => 'Gagal / Retur',
                                                default => $state,
                                            })
                                            ->color(fn (string $state): string => match ($state) {
                                                'pending' => 'gray',
                                                'loading' => 'warning',
                                                'shipping' => 'info',     // Biru untuk OTW
                                                'delivered' => 'success', // Hijau untuk Selesai
                                                'failed' => 'danger',
                                                default => 'gray',
                                            })
                                            ->icon(fn (string $state): string => match ($state) {
                                                'shipping' => 'heroicon-m-truck',
                                                'delivered' => 'heroicon-m-check-badge',
                                                'failed' => 'heroicon-m-x-circle',
                                                default => 'heroicon-m-clock',
                                            }),

                                        TextEntry::make('tanggal_penerimaan')
                                            ->label('Tanggal Jadwal')
                                            ->date('d F Y')
                                            ->icon('heroicon-m-calendar'),
                                    ]),

                                // Card Driver
                                InfolistSection::make('Armada & Kurir')
                                    ->compact()
                                    ->icon('heroicon-m-user-group')
                                    ->schema([
                                        TextEntry::make('karyawan.name') // Relasi ke User
                                            ->label('Driver')
                                            ->weight(FontWeight::Medium)
                                            ->icon('heroicon-m-user-circle'),

                                        // Jika ada data plat nomor di tabel user/driver
                                        // TextEntry::make('driver.plat_nomor')->label('Kendaraan'),
                                    ]),

                                // Card Bukti (Hanya muncul jika Delivered)
                                InfolistSection::make('Bukti Penerimaan')
                                    ->visible(fn ($record) => $record->status === 'delivered') // Conditional Visibility
                                    ->extraAttributes(['class' => 'border border-green-500/50 rounded-md']) // styling tipis hijau
                                    ->schema([
                                        ImageEntry::make('bukti_foto')
                                            ->hiddenLabel()
                                            ->height(150) // Tinggi gambar
                                            ->extraImgAttributes(['class' => 'rounded-lg shadow-md']), // Styling CSS class

                                        TextEntry::make('penerima_nama_asli')
                                            ->label('Diterima Oleh')
                                            ->icon('heroicon-m-pencil-square')
                                            ->weight(FontWeight::Bold),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_resi')
                    ->label('No. Resi')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('penjualan.no_nota')
                    ->label('No. Nota Penjualan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('penerima_no_hp')
                    ->label('No. HP Penerima')
                    ->searchable()
                    ->formatStateUsing(fn (?string $state) => $state ?: '-')
                    ->url(fn ($record) => $record->penerima_no_hp ? 'https://wa.me/'.$record->penerima_no_hp : null, true),
                TextColumn::make('alamat')
                    ->label('Alamat Pengiriman')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu Jadwal',
                        'loading' => 'Loading',
                        'shipping' => 'Dalam Perjalanan',
                        'delivered' => 'Terkirim',
                        'failed' => 'Gagal / Retur',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'loading' => 'warning',
                        'shipping' => 'info',
                        'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('tanggal_penerimaan')
                    ->label('Tanggal Kirim')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('driver.name')
                    ->label('Driver')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Jadwal',
                        'loading' => 'Loading',
                        'shipping' => 'Dalam Perjalanan',
                        'delivered' => 'Terkirim',
                        'failed' => 'Gagal / Retur',
                    ]),
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
            'index' => Pages\ListPenjadwalanPengirimen::route('/'),
            'create' => Pages\CreatePenjadwalanPengiriman::route('/create'),
            'view' => Pages\ViewPenjadwalanPengiriman::route('/{record}'),
            'edit' => Pages\EditPenjadwalanPengiriman::route('/{record}/edit'),
        ];
    }
}
