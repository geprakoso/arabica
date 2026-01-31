<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GajiKaryawanResource\Pages;
use App\Models\GajiKaryawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;

class GajiKaryawanResource extends Resource
{
    protected static ?string $model = GajiKaryawan::class;

    protected static ?string $navigationIcon = 'hugeicons-money-bag-02';

    protected static ?string $navigationGroup = 'Personalia';

    protected static ?string $label = 'Gaji Karyawan';

    protected static ?string $pluralLabel = 'Gaji Karyawan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section 1: Data Slip Gaji
                Forms\Components\Section::make('Data Slip Gaji')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->relationship('karyawan', 'nama_karyawan')
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-user')
                            ->required(),
                        Forms\Components\Select::make('tanggal_pemberian')
                            ->label('Periode Gaji')
                            ->options(function () {
                                $options = [];
                                // Generate dari 6 bulan ke depan hingga 36 bulan (3 tahun) ke belakang
                                // Diurutkan dari yang terbaru ke terlama agar lebih mudah dicari
                                for ($i = 6; $i >= -36; $i--) {
                                    $date = now()->addMonths($i)->endOfMonth();
                                    $options[$date->format('Y-m-d')] = $date->translatedFormat('F Y');
                                }

                                return $options;
                            })
                            ->default(now()->endOfMonth()->format('Y-m-d'))
                            ->searchable()
                            ->prefixIcon('heroicon-o-calendar')
                            ->required(),
                    ])->columns(2),

                // Section 2: Penerimaan (Table Repeater)
                Forms\Components\Section::make('Penerimaan (Earnings)')
                    ->icon('heroicon-o-plus-circle')
                    ->schema([
                        TableRepeater::make('penerimaan')
                            ->label('')
                            ->addActionLabel('Tambah Penerimaan')
                            ->reorderable(false)
                            ->schema([
                                Forms\Components\Select::make('kategori')
                                    ->options([
                                        'pokok' => 'Gaji Pokok',
                                        'penjualan' => 'Bonus Penjualan',
                                        'bonus' => 'Bonus Kinerja',
                                        'lembur' => 'Lembur',
                                        'tunjangan' => 'Tunjangan',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required()
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('catatan')
                                    ->placeholder('Opsional'),
                                Forms\Components\FileUpload::make('bukti')
                                    ->label('Bukti')
                                    ->disk('public')
                                    ->directory('gaji/bukti')
                                    ->visibility('public'),
                            ])
                            ->colStyles([
                                'kategori' => 'width: 25%;',
                                'nominal' => 'width: 20%;',
                                'catatan' => 'width: 35%;',
                                'bukti' => 'width: 20%;',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::updateTotals($set, $get);
                            }),
                    ]),

                // Section 3: Potongan (Table Repeater)
                Forms\Components\Section::make('Potongan (Deductions)')
                    ->icon('heroicon-o-minus-circle')
                    ->schema([
                        TableRepeater::make('potongan')
                            ->label('')
                            ->addActionLabel('Tambah Potongan')
                            ->reorderable(false)
                            ->schema([
                                Forms\Components\Select::make('kategori')
                                    ->options([
                                        'bpjs_kesehatan' => 'BPJS Kesehatan',
                                        'bpjs_ketenagakerjaan' => 'BPJS Ketenagakerjaan',
                                        'absensi' => 'Potongan Absensi',
                                        'cicilan' => 'Cicilan',
                                        'hutang' => 'Hutang',
                                        'pinjaman' => 'Pinjaman',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('catatan')
                                    ->placeholder('Opsional'),
                                Forms\Components\FileUpload::make('bukti')
                                    ->label('Bukti')
                                    ->disk('public')
                                    ->directory('gaji/bukti')
                                    ->visibility('public'),
                            ])
                            ->colStyles([
                                'kategori' => 'width: 25%;',
                                'nominal' => 'width: 20%;',
                                'catatan' => 'width: 35%;',
                                'bukti' => 'width: 20%;',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                self::updateTotals($set, $get);
                            }),
                    ]),

                // Summary Section
                Forms\Components\Section::make('Ringkasan Gaji')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Placeholder::make('summary_penerimaan')
                                ->label('Total Penerimaan (+)')
                                ->extraAttributes(['class' => 'text-success-600 font-medium'])
                                ->content(fn (Forms\Get $get) => 'Rp '.number_format($get('total_penerimaan') ?? 0, 0, ',', '.')),
                            Forms\Components\Placeholder::make('summary_potongan')
                                ->label('Total Potongan (-)')
                                ->extraAttributes(['class' => 'text-danger-600 font-medium'])
                                ->content(fn (Forms\Get $get) => 'Rp '.number_format($get('total_potongan') ?? 0, 0, ',', '.')),

                            Forms\Components\Placeholder::make('summary_gaji_bersih')
                                ->label('Take Home Pay')
                                ->content(fn (Forms\Get $get) => new \Illuminate\Support\HtmlString('<div class="rounded-lg bg-primary-50 p-4 border border-primary-200 dark:bg-primary-900/20 dark:border-primary-800"><span class="text-3xl font-extrabold text-primary-600 dark:text-primary-400">Rp '.number_format($get('gaji_bersih') ?? 0, 0, ',', '.').'</span></div>')),
                        ]),

                        Forms\Components\Hidden::make('total_penerimaan')->default(0),
                        Forms\Components\Hidden::make('total_potongan')->default(0),
                        Forms\Components\Hidden::make('gaji_bersih')->default(0),
                    ]),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Dibuat pada')
                            ->content(fn ($record) => $record?->created_at?->format('d M Y H:i') ?? '-'),
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Terakhir diubah')
                            ->content(fn ($record) => $record?->updated_at?->format('d M Y H:i') ?? '-'),
                    ])->collapsible(),
            ]);
    }

    protected static function updateTotals(Forms\Set $set, Forms\Get $get): void
    {
        $penerimaan = collect($get('penerimaan') ?? []);
        $potongan = collect($get('potongan') ?? []);

        $totalPenerimaan = $penerimaan->sum(fn ($i) => (float) ($i['nominal'] ?? 0));
        $totalPotongan = $potongan->sum(fn ($i) => (float) ($i['nominal'] ?? 0));
        $gajiBersih = $totalPenerimaan - $totalPotongan;

        $set('total_penerimaan', $totalPenerimaan);
        $set('total_potongan', $totalPotongan);
        $set('gaji_bersih', $gajiBersih);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('tanggal_pemberian')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_penerimaan')
                    ->label('Penerimaan')
                    ->money('IDR')
                    ->color('success')
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_potongan')
                    ->label('Potongan')
                    ->money('IDR')
                    ->color('danger')
                    ->alignRight()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('primary')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('karyawan_id')
                    ->relationship('karyawan', 'nama_karyawan')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari'),
                        Forms\Components\DatePicker::make('sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'], fn ($q, $date) => $q->whereDate('tanggal_pemberian', '>=', $date))
                            ->when($data['sampai'], fn ($q, $date) => $q->whereDate('tanggal_pemberian', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->color('info'),
                    Tables\Actions\EditAction::make()->color('primary'),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListGajiKaryawans::route('/'),
            'create' => Pages\CreateGajiKaryawan::route('/create'),
            'view' => Pages\ViewGajiKaryawan::route('/{record}'),
            'edit' => Pages\EditGajiKaryawan::route('/{record}/edit'),
        ];
    }
}
