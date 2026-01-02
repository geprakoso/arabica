<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Penjualan;
use App\Enums\MetodeBayar;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Livewire\Attributes\Title;
use Filament\Infolists\Infolist;
use App\Filament\Resources\BaseResource;
use function Laravel\Prompts\text;
use Filament\Infolists\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Carbon\CarbonInterface;

use App\Filament\Resources\PosActivityResource\Pages;
use App\Filament\Resources\PosActivityResource\Widgets\PosActivityStats;

class PosActivityResource extends BaseResource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationGroup = 'POS';

    protected static ?string $modelLabel = 'Aktivitas';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->posOnly())
            ->columns([
                Tables\Columns\TextColumn::make('no_nota')->label('Nota')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_penjualan')->date()->label('Tanggal'),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes()),
                Tables\Columns\TextColumn::make('metode_bayar')
                ->label('Metode Bayar')
                ->badge()
                    ->color(fn (MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'success',
                        MetodeBayar::CARD => 'info',
                        MetodeBayar::TRANSFER => 'gray',
                        MetodeBayar::EWALLET => 'warning',
                        default => 'secondary',
                    })
                    ->icon(fn (MetodeBayar $state) => match ($state) {
                        MetodeBayar::CASH => 'heroicon-o-currency-dollar',
                        MetodeBayar::CARD => 'heroicon-o-credit-card',
                        MetodeBayar::TRANSFER => 'heroicon-o-banknotes',
                        MetodeBayar::EWALLET => 'heroicon-o-wallet',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (?MetodeBayar $state) => $state?->label()),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->url(fn(Penjualan $record) => route('pos.receipt', $record))
                    ->icon('heroicon-o-printer')
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->label('Tanggal')
                    ->form([
                        DatePicker::make('tanggal')
                            ->native(false)
                            ->label('Pilih tanggal')
                            ->placeholder('Pilih tanggal (opsional)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $tanggal = $data['tanggal'] ?? null;

                        if ($tanggal instanceof CarbonInterface) {
                            $tanggal = $tanggal->toDateString();
                        }

                        if (! $tanggal) {
                            return $query;
                        }

                        return $query->whereDate('tanggal_penjualan', $tanggal);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosActivities::route('/'),
            'view' => Pages\ViewPosActivity::route('/{record}'),
        ];
    }

    /**
     * Menentukan apakah resource ini harus terdaftar di navigasi.
     *
     * Fungsi ini memeriksa apakah panel yang sedang aktif adalah panel 'pos'.
     * Jika ya, maka resource ini akan terdaftar di navigasi.
     *
     * @return bool True jika panel saat ini adalah 'pos', false jika tidak.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'pos'
            && static::canViewAny();
    }

    // mendapatkan widget yang akan di tampilkan di resource
    public static function getWidgets(): array
    {
        return [
            PosActivityStats::class,
        ];
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Info Transaksi')
                    ->columns(4)
                    ->compact()
                    ->description('Informasi Transaksi')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        TextEntry::make('no_nota')  
                            ->label('Nota')
                            ->icon('heroicon-o-receipt-refund')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('tanggal_penjualan')
                            ->label('Tanggal'),
                        TextEntry::make('member.nama_member')
                            ->label('Member')
                            ->formatStateUsing(fn ($state) => Str::title($state))
                            ->badge()
                            ->size('md')
                            ->color('success')
                            ->placeholder('-'),
                        TextEntry::make('karyawan.nama_karyawan')
                            ->label('Kasir')
                            ->placeholder('-'),
                        TextEntry::make('catatan')  
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('-'),

                        Section::make('')
                            ->schema([
                                Grid::make(5)->schema([
                                    TextEntry::make('metode_bayar')
                                        ->label('Metode Bayar')
                                        ->size('lg')
                                        ->formatStateUsing(fn (?MetodeBayar $state) => $state?->label())
                                        ->badge()
                                        ->color(fn (MetodeBayar $state) => match ($state) {
                                            MetodeBayar::CASH => 'success',
                                            MetodeBayar::CARD => 'info',
                                            MetodeBayar::TRANSFER => 'gray',
                                            MetodeBayar::EWALLET => 'warning',
                                            default => 'secondary',
                                        })
                                        ->icon(fn (MetodeBayar $state) => match ($state) {
                                            MetodeBayar::CASH => 'heroicon-o-currency-dollar',
                                            MetodeBayar::CARD => 'heroicon-o-credit-card',
                                            MetodeBayar::TRANSFER => 'heroicon-o-banknotes',
                                            MetodeBayar::EWALLET => 'heroicon-o-wallet',
                                            default => 'heroicon-o-question-mark-circle',
                                        })
                                        ->placeholder('-'),
                                    TextEntry::make('grand_total')
                                        ->size('lg')
                                        ->weight('bold')
                                        ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                        ->label('Grand Total'),
                                    TextEntry::make('tunai_diterima')
                                        ->label('Tunai Diterima')
                                        ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                        ->weight('bold')
                                        ->size('lg')
                                        ->placeholder('-'),
                                    TextEntry::make('diskon_total')
                                        ->label('Diskon')
                                        ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                        ->size('lg')
                                        ->weight('bold')
                                        ->placeholder('-'),
                                    TextEntry::make('kembalian')
                                        ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                        ->label('Kembalian')
                                        ->weight('bold')
                                        ->size('lg')
                                        ->placeholder('-'),
                                ]),
                            ])

                    ]),
                Section::make('Daftar Item')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->columnSpanFull()
                            ->schema([
                                section::make('')
                                    ->schema([
                                        Grid::make(11)->schema([
                                            TextEntry::make('produk.nama_produk')
                                                    ->label('Produk')
                                                    ->weight('semibold')
                                                    ->size('md')
                                                    ->columnSpan(5)
                                                    ->formatStateUsing(fn ($state) => strtoupper($state ?? '-')),
                                                TextEntry::make('qty')
                                                    ->label('Qty')
                                                    ->badge()
                                                    ->color('primary')
                                                    ->columnSpan(1),
                                                TextEntry::make('harga_jual')
                                                    ->label('Harga')
                                                    ->size('md')
                                                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                                    ->weight('semibold')
                                                    ->columnSpan(2),
                                            TextEntry::make('items_subtotal_display')
                                                    ->label('Subtotal')
                                                    ->weight('semibold')
                                                    ->size('md')
                                                    ->state(fn($record) => ($record->qty ?? 0) * ($record->harga_jual ?? 0) * 100)
                                                    ->formatStateUsing(fn ($state) => money($state, 'IDR')->formatWithoutZeroes())
                                                    ->columnSpan(2),
                                                    TextEntry::make('kondisi')
                                                        ->label('Kondisi')
                                                        ->badge()
                                                        ->color(fn ($state) => $state === 'baru' ? 'success' : 'warning')
                                                        ->columnSpan(1)
                                                        ->placeholder('-')
                                                        ->formatStateUsing(fn ($state) => strtoupper($state ?? '-')),
                                            ]),
                                            ])
                                    // ->extraAttributes([
                                    //     'class' => 'rounded-2xl border border-gray-200/80 bg-white/80 p-4 shadow-sm ring-gray-950/10 dark:border-white/10 dark:bg-gray-900/60 dark:ring-white/2',
                                    // ]),
                            ]),
                        // TextEntry::make('items_subtotal_display')
                        //     ->label('Subtotal Item')
                        //     ->state(fn(Penjualan $record) => $record->items?->sum(fn($item) => (float) ($item->harga_jual ?? 0) * (int) ($item->qty ?? 0)) ?? 0 )
                        //     ->moneyy('IDR')
                        //     ->columnSpanFull()
                        ]),       //     ->extraAttributes(['class' => 'text-left font-semibold text-lg mt-2']),
            ]);
    }
}
