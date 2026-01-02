<?php

namespace App\Filament\Resources\Akunting;

use App\Enums\KategoriAkun;
use App\Filament\Resources\Akunting\LaporanInputTransaksiResource\Pages;
use App\Models\InputTransaksiToko;
use Filament\Facades\Filament;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Grid as FormsGrid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use App\Filament\Resources\BaseResource;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use AlperenErsoy\FilamentExport\Actions\FilamentExportHeaderAction;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;

class LaporanInputTransaksiResource extends BaseResource
{
    // Kita gunakan Model yang sama, tapi Resource ini khusus Laporan
    protected static ?string $model = InputTransaksiToko::class;

    // Konfigurasi Navigasi & Label
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Input Transaksi';
    protected static ?string $modelLabel = 'Laporan Input Transaksi';
    protected static ?string $pluralModelLabel = 'Laporan Input Transaksi';
    protected static ?string $slug = 'laporan-input-transaksi';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Reports';

    // Matikan fitur Tambah Data (Create) karena ini hanya laporan view-only
    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            && static::canViewAny();
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
                    ->color(fn ($state) => $state instanceof KategoriAkun ? $state->getColor() : 'gray')
                    ->sortable(),

                TextColumn::make('jenisAkun.nama_jenis_akun')
                    ->label('Akun')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('keterangan_transaksi')
                    ->label('Keterangan')
                    ->limit(40)
                    ->wrap() // Agar teks panjang turun ke bawah
                    ->tooltip(fn ($state) => $state),

                // --- KOLOM NOMINAL (SUMMARIZER) ---
                TextColumn::make('nominal_transaksi')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignment(Alignment::End) // Rata kanan untuk angka
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('Total')
                            ->money('IDR'),
                    ]),
            ])
            ->defaultSort('tanggal_transaksi', 'desc')
            ->filters([
                Filter::make('filters')
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
                            DatePicker::make('dari_tanggal')
                                ->label('Mulai')
                                ->default(now()->startOfMonth())
                                ->native(false)
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                            DatePicker::make('sampai_tanggal')
                                ->label('Sampai')
                                ->default(now())
                                ->native(false)
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                        ]),
                        Select::make('kategori_transaksi')
                            ->label('Kategori')
                            ->options(KategoriAkun::class)
                            ->columnSpan(2)
                            ->native(false)
                    ])->columns(2)
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
                            $startDate = $data['dari_tanggal'] ?? null;
                            $endDate = $data['sampai_tanggal'] ?? $endDate;
                        }

                        return $query
                            ->when(
                                $startDate,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $endDate,
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_transaksi', '<=', $date),
                            )
                            ->when(
                                $data['kategori_transaksi'],
                                fn (Builder $query, $kategori): Builder => $query->where('kategori_transaksi', $kategori),
                            );
                    })
            ], layout: FiltersLayout::Dropdown)
            ->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->label('Filter')
                    ->icon('heroicon-m-funnel')
            )
            ->headerActions([
                FilamentExportHeaderAction::make('export')
                    ->label('Export')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->extraAttributes(['class' => 'hidden'])
                    ->modalSubmitAction(fn ($action) => $action->color('gray'))
                    ->modalCancelAction(fn ($action) => $action->color('gray'))
                    ->modalFooterActions(function (FilamentExportHeaderAction $action): array {
                        $footerActions = $action->getExportModalActions();

                        $printColor = collect($footerActions)
                            ->first(fn (StaticAction $footerAction) => $footerAction->getName() === 'print')
                            ?->getColor() ?? 'gray';

                        return collect($footerActions)
                            ->map(function (StaticAction $footerAction) use ($printColor) {
                                if (in_array($footerAction->getName(), ['preview', 'submit', 'cancel'])) {
                                    $footerAction->color($printColor);
                                }

                                return $footerAction;
                            })
                            ->all();
                    })
                    ->fileName('laporan-transaksi-toko')
                    ->defaultFormat('xlsx')
                    ->withHiddenColumns()
                    ->formatStates([
                        'nominal_transaksi' => fn (InputTransaksiToko $record) => (int) $record->nominal_transaksi,
                    ])
                    ->columnFormats([
                        'nominal_transaksi' => '[$Rp-421] #,##0.00',
                    ])
                    ->disableAdditionalColumns()
                    ->filterColumnsFieldLabel('Pilih kolom untuk diexport'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (InputTransaksiToko $record) => static::getUrl(
                        'view',
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId(),
                    )),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    FilamentExportBulkAction::make('export_selected')
                        ->label('Export (Pilih Baris)')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->modalSubmitAction(fn ($action) => $action->color('gray'))
                        ->modalCancelAction(fn ($action) => $action->color('gray'))
                        ->fileName('laporan-transaksi-terpilih')
                        ->defaultFormat('xlsx')
                        ->withHiddenColumns()
                        ->formatStates([
                            'nominal_transaksi' => fn (InputTransaksiToko $record) => (int) $record->nominal_transaksi,
                        ])
                        ->columnFormats([
                            'nominal_transaksi' => '[$Rp-421] #,##0.00',
                        ])
                        ->disableAdditionalColumns()
                        ->filterColumnsFieldLabel('Pilih kolom untuk diexport'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3)
            ->schema([
                // === KOLOM KIRI (INFORMASI UTAMA) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        InfolistSection::make('Informasi Utama')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                // Baris 1: Tanggal & Kategori
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('tanggal_transaksi')
                                            ->label('Tanggal Transaksi')
                                            ->date('d F Y')
                                            ->icon('heroicon-m-calendar'),

                                        TextEntry::make('kategori_transaksi')
                                            ->label('Kategori')
                                            ->badge()
                                            ->size(TextEntrySize::Medium)
                                            ->color(function ($state) {
                                                $enum = $state instanceof KategoriAkun ? $state : KategoriAkun::tryFrom($state);
                                                return $enum?->getColor() ?? 'gray';
                                            }),
                                    ]),

                                // Baris 2: Nominal (Dibuat Besar)
                                TextEntry::make('nominal_transaksi')
                                    ->label('Nominal Transaksi')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->columnSpanFull()
                                    ->color('primary'), // Highlight warna
                            ]),

                        InfolistSection::make('Detail Akun & Catatan')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        TextEntry::make('jenisAkun.nama_jenis_akun')
                                            ->label('Jenis Akun')
                                            ->icon('heroicon-m-credit-card'),

                                        TextEntry::make('akunTransaksi.nama_akun')
                                            ->label('Akun Transaksi')
                                            ->placeholder('-')
                                            ->icon('heroicon-m-building-library'),
                                    ]),

                                TextEntry::make('keterangan_transaksi')
                                    ->label('Keterangan / Catatan')
                                    ->markdown()
                                    ->prose() // Agar format teks lebih enak dibaca
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // === KOLOM KANAN (SIDEBAR - BUKTI) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        InfolistSection::make('Bukti Fisik')
                            ->icon('heroicon-m-paper-clip')
                            ->collapsible()
                            ->schema([
                                ViewEntry::make('bukti_transaksi_gallery')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->view('filament.infolists.components.media-manager-gallery')
                                    ->state(fn (InputTransaksiToko $record) => $record->buktiTransaksiGallery()),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanInputTransaksis::route('/'),
            // 'view' tidak wajib didefinisikan jika menggunakan action modal,
            // tapi jika ingin halaman terpisah, biarkan baris di bawah ini:
            'view' => Pages\ViewLaporanInputTransaksi::route('/{record}'),
        ];
    }
}
