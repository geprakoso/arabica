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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ViewEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use App\Models\JenisAkun;
use App\Filament\Forms\Components\MediaManagerPicker;

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
                                Select::make('kode_jenis_akun_id')
                                    ->label('Jenis Akun')
                                    ->required()
                                    ->relationship(
                                        name: 'jenisAkun',
                                        titleAttribute: 'nama_jenis_akun',
                                        modifyQueryUsing: fn (Builder $query, Get $get) => $get('kategori_transaksi')
                                            ? $query->whereHas('kodeAkun', fn (Builder $q) => $q->where('kategori_akun', $get('kategori_transaksi')))
                                            : $query,
                                    )
                                    ->searchable(['nama_jenis_akun', 'kode_jenis_akun'])
                                    ->preload()
                                    // ->clearable()
                                    ->native(false)
                                    ->prefixIcon('hugeicons-credit-card')
                                    ->placeholder('Pilih jenis akun')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (JenisAkun $record): HtmlString => self::formatJenisAkunLabelWithBadge($record)
                                    )
                                    ->allowHtml()
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        if (blank($state)) {
                                            $set('kategori_transaksi', null);
                                            return;
                                        }

                                        $kategori = JenisAkun::query()
                                            ->with('kodeAkun')
                                            ->find($state)
                                            ?->kodeAkun
                                            ?->kategori_akun;

                                        $set('kategori_transaksi', $kategori);
                                    }),
                            ]),

                        // --- BARIS 2: Detail Akun ---
                        FormsGrid::make(2)
                            ->schema([
                                Select::make('kategori_transaksi')
                                    ->label('Kategori')
                                    ->required()
                                    ->options(KategoriAkun::class)
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Pilih kategori untuk filter')
                                    ->prefixIcon('hugeicons-tag-01')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                        $selectedJenis = $get('kode_jenis_akun_id');
                                        if (! $selectedJenis) {
                                            return;
                                        }

                                        $kategoriJenis = JenisAkun::query()
                                            ->with('kodeAkun')
                                            ->find($selectedJenis)
                                            ?->kodeAkun
                                            ?->kategori_akun;

                                        if ($kategoriJenis !== $state) {
                                            $set('kode_jenis_akun_id', null);
                                        }
                                    })
                                    ->dehydrated(),

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
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                            // ->stripCharacters([',', '.', 'Rp', ' '])
                            ->columnSpanFull(), // Nominal dibuat lebar agar fokus

                        Textarea::make('keterangan_transaksi')
                            ->label('Keterangan / Catatan')
                            ->placeholder('Contoh: Pembayaran listrik bulan November...')
                            ->rows(3)
                            ->columnSpanFull(),

                        // --- BARIS 4: Bukti ---
                        MediaManagerPicker::make('bukti_transaksi')
                            ->label('Upload Bukti (Gallery)')
                            ->disk('public')
                            ->maxItems(10)
                            ->reorderable()
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
                                    // ->money('idr', false)
                                    ->weight(FontWeight::Bold)
                                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
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
                                            ->weight(FontWeight::Medium)
                                            ->formatStateUsing(
                                                fn ($state, InputTransaksiToko $record): string => self::formatJenisAkunLabel($record->jenisAkun)
                                            ),

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
                                ViewEntry::make('bukti_transaksi_gallery')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.components.media-manager-gallery')
                                    ->state(fn (InputTransaksiToko $record) => $record->buktiTransaksiGallery()),
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
                    ->sortable()
                    ->formatStateUsing(
                        fn ($state, InputTransaksiToko $record): string => self::formatJenisAkunLabel($record->jenisAkun)
                    ),
                Tables\Columns\TextColumn::make('kategori_transaksi')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (?KategoriAkun $state) => $state?->getLabel() ?? '-')
                    ->color(fn (?KategoriAkun $state) => $state?->getColor()),
                Tables\Columns\TextColumn::make('nominal_transaksi')
                    ->label('Nominal')
                    // ->money('idr', false)
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
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
                Tables\Filters\Filter::make('filters')
                    ->form([
                        FormsGrid::make(2)->schema([
                            Select::make('range')
                                ->label('Rentang Waktu')
                                ->options([
                                    '1m' => '1 Bulan',
                                    '3m' => '3 Bulan',
                                    '6m' => '6 Bulan',
                                    '1y' => '1 Tahun',
                                    'custom' => 'Custom',
                                ])
                                ->default('1m')
                                ->native(false)
                                ->reactive()
                                ->columnSpan(2),
                            DatePicker::make('from')
                                ->label('Mulai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('hugeicons-calendar-01')
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                            DatePicker::make('until')
                                ->label('Sampai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('hugeicons-calendar-01')
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $range = $data['range'] ?? '1m';

                        $startDate = null;
                        $endDate = now();

                        if ($range !== 'custom') {
                            $startDate = match ($range) {
                                '3m' => now()->copy()->subMonthsNoOverflow(3),
                                '6m' => now()->copy()->subMonthsNoOverflow(6),
                                '1y' => now()->copy()->subYear(),
                                default => now()->copy()->subMonth(),
                            };
                        } else {
                            $startDate = $data['from'] ?? null;
                            $endDate = $data['until'] ?? $endDate;
                        }

                        return $query
                            ->when(
                                $startDate,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $endDate,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (InputTransaksiToko $record) => static::getUrl(
                        'view',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
                Tables\Actions\EditAction::make()
                    ->url(fn (InputTransaksiToko $record) => static::getUrl(
                        'edit',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
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

    protected static function formatJenisAkunLabel(?JenisAkun $jenisAkun): string
    {
        if (! $jenisAkun) {
            return '-';
        }

        $kodeJenisAkun = $jenisAkun->kode_jenis_akun;
        $namaJenis = $jenisAkun->nama_jenis_akun;
        $label = trim(sprintf('%s - %s', $kodeJenisAkun, $namaJenis), ' -');

        return $label !== '' ? $label : ($kodeJenisAkun ?? $namaJenis ?? '-');
    }

    protected static function formatJenisAkunLabelWithBadge(?JenisAkun $jenisAkun): HtmlString
    {
        if (! $jenisAkun) {
            return new HtmlString('-');
        }

        $kodeJenisAkun = $jenisAkun->kode_jenis_akun;
        $namaJenis = $jenisAkun->nama_jenis_akun;
        $label = trim(sprintf('%s - %s', $kodeJenisAkun, $namaJenis), ' -');

        $kategori = $jenisAkun->kodeAkun?->kategori_akun;
        $kategoriEnum = KategoriAkun::tryFrom((string) $kategori);
        $kategoriLabel = $kategoriEnum?->getLabel();
        $color = $kategoriEnum?->getColor() ?? 'gray';

        if ($kategoriLabel) {
            $badgeClasses = 'fi-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-1.5 py-0.5';

            if ($color === 'gray') {
                $badgeClasses .= ' bg-gray-50 text-gray-600 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20';
                $badgeStyle = '';
            } else {
                $badgeClasses .= ' fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30';
                $badgeStyle = \Filament\Support\get_color_css_variables($color, shades: [50, 400, 600], alias: 'badge');
            }

            $badge = sprintf(
                '<span class="%s shrink-0" %s>%s</span>',
                e($badgeClasses),
                $badgeStyle ? 'style="' . e($badgeStyle) . '"' : '',
                e($kategoriLabel)
            );

            return new HtmlString(
                '<span class="flex items-center justify-between gap-2">' .
                    '<span class="min-w-0 truncate">' . e($label) . '</span>' .
                    $badge .
                '</span>'
            );
        }

        return new HtmlString(e($label !== '' ? $label : ($kodeJenisAkun ?? $namaJenis ?? '-')));
    }
}
