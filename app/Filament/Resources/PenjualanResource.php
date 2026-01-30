<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenjualanResource\Pages;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\PenjualanResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PenjualanResource\RelationManagers\JasaRelationManager;
use App\Models\Member;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Support\WebpUpload;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Group as InfoGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PenjualanResource extends BaseResource
{
    protected static ?string $model = Penjualan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Input Penjualan';

    protected static ?string $pluralLabel = 'Input Penjualan';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'no_nota';

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_nota', 'member.nama_member'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Member' => $record->member?->nama_member ?? 'Umum',
            'Tanggal' => $record->tanggal_penjualan->format('d M Y'),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // === BAGIAN 1: INFORMASI PENJUALAN ===
                Section::make('Informasi Penjualan')
                    ->icon('heroicon-o-document-text')
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
                            ->default(fn() => Auth::user()->karyawan?->id)
                            ->required()
                            ->native(false),
                        Select::make('id_member')
                            ->label('Member')
                            ->searchable()
                            ->relationship('member', 'nama_member')
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
                                    Select::make('provinsi')
                                        ->label('Provinsi')
                                        ->searchable()
                                        ->options(fn() => Province::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'name')
                                            ->all())
                                        ->live()
                                        ->afterStateUpdated(function (callable $set): void {
                                            $set('kota', null);
                                            $set('kecamatan', null);
                                        })
                                        ->placeholder('Pilih provinsi'),
                                    Select::make('kota')
                                        ->label('Kota/Kabupaten')
                                        ->searchable()
                                        ->options(function (Get $get): array {
                                            $provinceName = $get('provinsi');
                                            if (! $provinceName) {
                                                return [];
                                            }

                                            $provinceCode = Province::query()
                                                ->where('name', $provinceName)
                                                ->value('code');

                                            if (! $provinceCode) {
                                                return [];
                                            }

                                            return City::query()
                                                ->where('province_code', $provinceCode)
                                                ->orderBy('name')
                                                ->pluck('name', 'name')
                                                ->all();
                                        })
                                        ->live()
                                        ->afterStateUpdated(fn($set) => $set('kecamatan', null))
                                        ->placeholder('Pilih kota/kabupaten'),
                                    Select::make('kecamatan')
                                        ->label('Kecamatan')
                                        ->searchable()
                                        ->options(function (Get $get): array {
                                            $cityName = $get('kota');
                                            if (! $cityName) {
                                                return [];
                                            }

                                            $cityCode = City::query()
                                                ->where('name', $cityName)
                                                ->value('code');

                                            if (! $cityCode) {
                                                return [];
                                            }

                                            return District::query()
                                                ->where('city_code', $cityCode)
                                                ->orderBy('name')
                                                ->pluck('name', 'name')
                                                ->all();
                                        })
                                        ->placeholder('Pilih kecamatan'),
                                ]),
                            ]),
                    ])
                    ->columns(2),

                // === BAGIAN 2: DAFTAR PRODUK ===
                Section::make('Daftar Produk')
                    ->icon('heroicon-o-shopping-cart')
                    ->description('Pilih produk yang dijual')
                    ->schema([
                        TableRepeater::make('items_temp')
                            ->label('')
                            ->minItems(0)
                            ->reorderable(false)
                            ->addActionLabel('Tambah Produk')
                            ->colStyles([
                                'id_produk' => 'width: 30%;',
                                'kondisi' => 'width: 12%;',
                                'qty' => 'width: 8%;',
                                'hpp' => 'width: 15%;',
                                'harga_jual' => 'width: 15%;',
                                'serials_count' => 'width: 15%;',
                            ])
                            ->childComponents([
                                Select::make('id_produk')
                                    ->label('Produk')
                                    ->options(function (Get $get): array {
                                        $options = self::getAvailableProductOptions();
                                        $items = $get('../../items_temp') ?? [];
                                        $includeIds = collect($items)
                                            ->pluck('id_produk')
                                            ->filter()
                                            ->unique()
                                            ->values()
                                            ->all();

                                        if (! empty($includeIds)) {
                                            $extras = Produk::query()
                                                ->whereIn('id', $includeIds)
                                                ->orderBy('nama_produk')
                                                ->pluck('nama_produk', 'id')
                                                ->all();
                                            foreach ($extras as $id => $label) {
                                                if (! array_key_exists($id, $options)) {
                                                    $extras[$id] = $label . ' (stok habis)';
                                                }
                                            }
                                            $options = $options + $extras;
                                        }

                                        return $options;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state, Get $get): void {
                                        $set('harga_jual', null);
                                        $set('kondisi', null);
                                        $set('serials', []);

                                        if ($state) {
                                            // Get default price from oldest batch
                                            $batch = self::getOldestAvailableBatch($state);
                                            if ($batch) {
                                                $set('harga_jual', $batch->harga_jual);
                                                $set('hpp', $batch->hpp);
                                                $set('kondisi', $batch->kondisi);
                                            }
                                        }
                                    }),
                                Select::make('kondisi')
                                    ->label('Kondisi')
                                    ->options(function (Get $get): array {
                                        $productId = (int) ($get('id_produk') ?? 0);

                                        return self::getConditionOptions($productId);
                                    })
                                    ->native(false)
                                    ->placeholder('Otomatis')
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                                        $productId = (int) ($get('id_produk') ?? 0);
                                        if ($productId > 0) {
                                            // Get price and hpp for this condition
                                            $batch = self::getOldestAvailableBatch($productId, $state);
                                            if ($batch) {
                                                $set('harga_jual', $batch->harga_jual);
                                                $set('hpp', $batch->hpp);
                                            }
                                        }
                                    }),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(function (Get $get): ?int {
                                        $productId = (int) ($get('id_produk') ?? 0);
                                        if ($productId < 1) {
                                            return null;
                                        }
                                        $condition = $get('kondisi');

                                        return self::getAvailableQty($productId, $condition) ?: null;
                                    })
                                    ->required()
                                    ->live(onBlur: true)
                                    ->placeholder(function (Get $get): string {
                                        $productId = (int) ($get('id_produk') ?? 0);
                                        if ($productId < 1) {
                                            return '';
                                        }
                                        $condition = $get('kondisi');
                                        $available = self::getAvailableQty($productId, $condition);

                                        return 'Stok: ' . number_format($available, 0, ',', '.');
                                    })
                                    ->validationMessages([
                                        'max' => 'Stok tidak cukup! Maksimal :max unit.',
                                    ])
                                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state): void {
                                        // Reset serials when qty changes
                                        $qty = (int) ($state ?? 0);
                                        $serials = $get('serials') ?? [];

                                        // Adjust serials array to match qty
                                        if (count($serials) > $qty) {
                                            $serials = array_slice($serials, 0, $qty);
                                        }
                                        while (count($serials) < $qty) {
                                            $serials[] = ['sn' => '', 'garansi' => ''];
                                        }
                                        $set('serials', $serials);
                                    }),
                                TextInput::make('hpp')
                                    ->label('HPP')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->readOnly()
                                    ->dehydrated(true),
                                TextInput::make('harga_jual')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required(),

                                // Hidden field to store serial data
                                Hidden::make('serials')
                                    ->default([])
                                    ->dehydrated(true),

                                // Serial count display with modal action
                                TextInput::make('serials_count')
                                    ->label('SN & Garansi')
                                    ->formatStateUsing(fn(Get $get): string => count(array_filter($get('serials') ?? [], fn($s) => ! empty($s['sn']))) . ' SN')
                                    ->live()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffixAction(
                                        FormAction::make('manage_serials')
                                            ->label('Isi')
                                            ->icon('heroicon-o-qr-code')
                                            ->button()
                                            ->color('info')
                                            ->modalHeading('Serial Number & Garansi')
                                            ->modalWidth('2xl')
                                            ->fillForm(function (Get $get): array {
                                                $existingSerials = $get('serials') ?? [];
                                                $qty = (int) ($get('qty') ?? 0);

                                                // If we have existing serials, use them
                                                if (count($existingSerials) > 0) {
                                                    return ['serials_temp' => $existingSerials];
                                                }

                                                // Otherwise, create empty rows based on qty
                                                $serials = [];
                                                for ($i = 0; $i < $qty; $i++) {
                                                    $serials[] = ['sn' => '', 'garansi' => ''];
                                                }

                                                return ['serials_temp' => $serials];
                                            })
                                            ->form([
                                                TableRepeater::make('serials_temp')
                                                    ->label('')
                                                    ->schema([
                                                        TextInput::make('sn')
                                                            ->label('Serial Number')
                                                            ->placeholder('Masukkan SN'),
                                                        TextInput::make('garansi')
                                                            ->label('Garansi')
                                                            ->placeholder('Contoh: 1 Tahun'),
                                                    ])
                                                    ->defaultItems(0)
                                                    ->addActionLabel('+ Tambah Serial')
                                                    ->reorderable(false)
                                                    ->colStyles([
                                                        'sn' => 'width: 60%;',
                                                        'garansi' => 'width: 40%;',
                                                    ]),
                                            ])
                                            ->action(function (Set $set, array $data): void {
                                                $set('serials', $data['serials_temp'] ?? []);
                                            })
                                            ->after(function (Set $set, Get $get): void {
                                                // Force refresh of serials_count
                                                $serials = $get('serials') ?? [];
                                                $filledCount = count(array_filter($serials, fn($s) => ! empty($s['sn'])));
                                                $set('serials_count', $filledCount . ' SN');
                                            })
                                    ),
                            ]),
                    ]),

                // === BAGIAN 3: DAFTAR JASA ===
                Section::make('Daftar Jasa')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->description('Jasa yang diberikan')
                    ->collapsed()
                    ->schema([
                        TableRepeater::make('jasaItems')
                            ->label('')
                            ->relationship('jasaItems')
                            ->minItems(0)
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Jasa')
                            ->colStyles([
                                'pembelian_jasa_id' => 'width: 20%;',
                                'jasa_id' => 'width: 25%;',
                                'qty' => 'width: 10%;',
                                'harga' => 'width: 20%;',
                                'catatan' => 'width: 25%;',
                            ])
                            ->childComponents([
                                Select::make('pembelian_jasa_id')
                                    ->label('Referensi Nota')
                                    ->relationship('pembelianJasa', 'id_pembelian_jasa', fn(Builder $query) => $query->with(['pembelian', 'jasa']))
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        $nota = $record->pembelian->no_po ?? $record->pembelian->nota_supplier ?? 'No Nota';
                                        $jasa = $record->jasa->nama_jasa ?? 'Jasa';

                                        return "{$nota} - {$jasa}";
                                    })
                                    ->searchable(['id_pembelian_jasa', 'id_pembelian'])
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\PembelianJasa::query()
                                            ->whereHas('pembelian', function ($q) use ($search) {
                                                $q->where(function ($qq) use ($search) {
                                                    $qq->where('no_po', 'like', "%{$search}%")
                                                        ->orWhere('nota_supplier', 'like', "%{$search}%");
                                                });
                                            })
                                            ->orWhereHas('jasa', function ($q) use ($search) {
                                                $q->where('nama_jasa', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                $nota = $item->pembelian->no_po ?? $item->pembelian->nota_supplier ?? 'No Nota';
                                                $jasa = $item->jasa->nama_jasa ?? 'Jasa';

                                                return [$item->id_pembelian_jasa => "{$nota} - {$jasa}"];
                                            });
                                    })
                                    ->placeholder('Pilih Nota Pembelian')
                                    ->preload()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state, Get $get): void {
                                        if ($state) {
                                            $pembelianJasa = \App\Models\PembelianJasa::with('jasa')->find($state);
                                            if ($pembelianJasa) {
                                                $set('jasa_id', $pembelianJasa->jasa_id);
                                                $set('harga', $pembelianJasa->harga);
                                            }
                                        }
                                    }),

                                Select::make('jasa_id')
                                    ->label('Jasa')
                                    ->options(function (Get $get) {
                                        $pembelianJasaId = $get('pembelian_jasa_id');
                                        $query = \App\Models\Jasa::query()->where('is_active', true);

                                        if ($pembelianJasaId) {
                                            $pembelianJasa = \App\Models\PembelianJasa::find($pembelianJasaId);
                                            if ($pembelianJasa) {
                                                $query->whereKey($pembelianJasa->jasa_id);
                                            }
                                        }

                                        return $query->pluck('nama_jasa', 'id');
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                        if ($state) {
                                            $harga = \App\Models\Jasa::find($state)?->harga;
                                            $set('harga', $harga);
                                        }
                                    }),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('harga')
                                    ->label('Tarif')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required(),
                                // TextInput::make('catatan')
                                //     ->label('Catatan')
                                //     ->placeholder('Opsional'),
                            ])->colStyles([
                                'referensi' => 'width: 20%',
                                'jasa' => 'width: 35%',
                                'qty' => 'width: 7%',
                                'harga' => 'width: 25%',
                            ]),
                    ]),

                // === BAGIAN: GRAND TOTAL ===
                Section::make('Grand Total')
                    ->description('Total tagihan ke pelanggan')
                    ->icon('heroicon-m-calculator')
                    ->schema([
                        Placeholder::make('grand_total')
                            ->label('Grand Total')
                            ->content(function (Get $get): string {
                                // Calculate Product Total
                                $items = $get('items_temp') ?? [];
                                $productTotal = collect($items)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga_jual'] ?? 0));

                                // Calculate Service Total
                                $jasaItems = $get('jasaItems') ?? [];
                                $serviceTotal = collect($jasaItems)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga'] ?? 0));

                                // Get Discount
                                $diskon = (int) ($get('diskon_total') ?? 0);

                                // Calculate Grand Total
                                $grandTotal = max(0, ($productTotal + $serviceTotal) - $diskon);

                                return 'Rp ' . number_format($grandTotal, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                    ])
                    ->collapsed(false),

                // === BAGIAN 4: DISKON & PEMBAYARAN ===
                Section::make('Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        TextInput::make('diskon_total')
                            ->label('Diskon')
                            ->prefix('Rp')
                            ->numeric()
                            ->default(0)
                            ->live()
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0),
                        TableRepeater::make('pembayaran')
                            ->label('Pembayaran (Split)')
                            ->relationship('pembayaran')
                            ->minItems(0)
                            ->addable(function (Get $get): bool {
                                // Grand Total
                                $items = $get('items_temp') ?? [];
                                $productTotal = collect($items)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga_jual'] ?? 0));
                                $jasaItems = $get('jasaItems') ?? [];
                                $serviceTotal = collect($jasaItems)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga'] ?? 0));
                                $diskon = (int) ($get('diskon_total') ?? 0);
                                $grandTotal = max(0, ($productTotal + $serviceTotal) - $diskon);

                                // Paid
                                $payments = $get('pembayaran') ?? [];
                                $paidTotal = collect($payments)->sum(fn($p) => (int) ($p['jumlah'] ?? 0));

                                return $grandTotal > $paidTotal;
                            })
                            ->addActionLabel('Tambah Pembayaran')
                            ->colStyles([
                                'tanggal' => 'width: 15%;',
                                'metode_bayar' => 'width: 15%;',
                                'akun_transaksi_id' => 'width: 20%;',
                                'jumlah' => 'width: 25%;',
                                'bukti_transfer' => 'width: 25%;',
                            ])
                            ->live() // Update availability when fields change
                            ->childComponents([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->native(false)
                                    ->required(),
                                Select::make('metode_bayar')
                                    ->label('Metode')
                                    ->placeholder('pilih')
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
                                    ->placeholder('pilih')
                                    ->native(false)
                                    ->required(fn(Get $get) => $get('metode_bayar') === 'transfer'),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->live()
                                    ->placeholder(function (Get $get, Component $component): string {
                                        // Grand Total
                                        $items = $get('../../items_temp') ?? [];
                                        $productTotal = collect($items)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga_jual'] ?? 0));
                                        $jasaItems = $get('../../jasaItems') ?? [];
                                        $serviceTotal = collect($jasaItems)->sum(fn($item) => (int) ($item['qty'] ?? 0) * (int) ($item['harga'] ?? 0));
                                        $diskon = (int) ($get('../../diskon_total') ?? 0);
                                        $grandTotal = max(0, ($productTotal + $serviceTotal) - $diskon);

                                        // Previous Payments
                                        $payments = $get('../../pembayaran') ?? [];
                                        $itemPath = $component->getContainer()->getStatePath();
                                        $parts = explode('.', $itemPath);
                                        $myUuid = end($parts);

                                        $previousPaid = 0;
                                        foreach ($payments as $uuid => $data) {
                                            if ($uuid === $myUuid) {
                                                break;
                                            }
                                            $previousPaid += (int) ($data['jumlah'] ?? 0);
                                        }

                                        $remaining = max(0, $grandTotal - $previousPaid);

                                        return 'Rp ' . number_format($remaining, 0, ',', '.');
                                    })
                                    ->required(),
                                FileUpload::make('bukti_transfer')
                                    ->label('Bukti')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('penjualan/bukti-transfer')
                                    ->imageResizeMode('contain')
                                    ->imageResizeTargetWidth('1920')
                                    ->imageResizeTargetHeight('1080')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->saveUploadedFileUsing(function (BaseFileUpload $component, TemporaryUploadedFile $file): ?string {
                                        return WebpUpload::store($component, $file, 80);
                                    })
                                    ->openable()
                                    ->downloadable()
                                    ->previewable(false)
                                    // ->placeholder('Upload bukti transfer')
                                    ->extraAttributes(['class' => 'compact-file-upload'])
                                    ->helperText(new HtmlString('
                                                <style>
                                                    .compact-file-upload .filepond--root,
                                                    .compact-file-upload .filepond--panel-root {
                                                        min-height: 38px !important;
                                                        height: 38px !important;
                                                        border-radius: 0.5rem;
                                                    }
                                                    .compact-file-upload .filepond--drop-label {
                                                        min-height: 38px !important;
                                                        display: flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        transform: none !important;
                                                        padding: 0 !important;
                                                        color: rgb(var(--primary-600)) !important;
                                                        cursor: pointer;
                                                    }
                                                </style>
                                            ')),
                            ])
                            ->columns(5),

                    ])
                    ->columns(2),
                Section::make('Catatan')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        RichEditor::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query
                ->with(['items', 'jasaItems'])
                ->withCount(['items', 'jasaItems'])
                ->withSum('pembayaran', 'jumlah'))
            ->defaultSort('created_at', 'desc')
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
                    ->searchable()
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
                TextColumn::make('is_nerfed')
                    ->label('Nerf')
                    ->badge()
                    ->state(fn(Penjualan $record): ?string => $record->is_nerfed ? 'Nerf' : null)
                    ->color('danger')
                    ->icon('heroicon-m-fire')
                    ->tooltip('Data pembelian terkait telah dihapus paksa')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('sisa_bayar_display')
                    ->label('Sisa Bayar')
                    ->alignRight()
                    ->state(function (Penjualan $record): string {
                        $grandTotal = self::calculateGrandTotal($record);
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
                TextColumn::make('items_serials')
                    ->label('SN')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->state(function (Penjualan $record): string {
                        $allSerials = $record->items
                            ->flatMap(fn($item) => collect($item->serials ?? [])->pluck('sn'))
                            ->filter()
                            ->values();

                        if ($allSerials->isEmpty()) {
                            return '-';
                        }

                        return $allSerials->implode(', ');
                    })
                    ->wrap()
                    ->limit(30)
                    ->tooltip(function (Penjualan $record): ?string {
                        $allSerials = $record->items
                            ->flatMap(fn($item) => collect($item->serials ?? [])->pluck('sn'))
                            ->filter()
                            ->values();

                        return $allSerials->count() > 0 ? $allSerials->implode(', ') : null;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('items', function (Builder $q) use ($search): void {
                            $q->whereRaw("JSON_SEARCH(serials, 'one', ?, NULL, '$[*].sn') IS NOT NULL", ["%{$search}%"]);
                        });
                    }),
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
                ActionGroup::make([
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
                ])
                    ->label('Invoice')
                    ->icon('heroicon-m-printer')
                    ->tooltip('Invoice'),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->tooltip('Lihat Detail'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-m-pencil-square')
                        ->tooltip('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-m-trash')
                        ->hidden(
                            fn(Penjualan $record): bool => ! auth()->user()?->hasRole('godmode') && ($record->sumber_transaksi === 'tukar_tambah' || $record->tukarTambah()->exists())
                        )
                        ->tooltip(
                            fn(Penjualan $record): ?string => (! auth()->user()?->hasRole('godmode') && ($record->sumber_transaksi === 'tukar_tambah' || $record->tukarTambah()->exists()))
                                ? 'Hapus dari Tukar Tambah'
                                : null
                        )
                        ->action(function (Penjualan $record, \Filament\Tables\Actions\DeleteAction $action) {
                            $livewire = $action->getLivewire();

                            // Godmode / Advanced Flow
                            if (auth()->user()?->hasRole('godmode')) {
                                $livewire->deleteRecordId = $record->getKey();

                                if ($record->is_nerfed) {
                                    // Nerfed -> Step 3 (Password)
                                    $livewire->replaceMountedAction('deleteStep3');
                                } else {
                                    // Normal -> Step 2 (Impact)
                                    $livewire->replaceMountedAction('deleteStep2');
                                }

                                return;
                            }

                            // Regular User: Standard Delete
                            $record->delete();
                            \Filament\Notifications\Notification::make()->title('Penjualan dihapus')->success()->send();
                        }),
                ])->hidden(function (Penjualan $record): bool {
                    // Godmode: Always show actions
                    if (auth()->user()?->hasRole('godmode')) {
                        return false;
                    }

                    // Always show actions for Tukar Tambah records (at least View)
                    if ($record->sumber_transaksi === 'tukar_tambah' || $record->tukarTambah()->exists()) {
                        return false;
                    }

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

                                TextEntry::make('is_nerfed')
                                    ->label('Status')
                                    ->badge()
                                    ->state(fn(Penjualan $record): ?string => $record->is_nerfed ? '⚠️ Nerf' : null)
                                    ->color('danger')
                                    ->visible(fn(Penjualan $record): bool => $record->is_nerfed ?? false),
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
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
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
                    ->visible(fn(Penjualan $record) => $record->jasaItems->isNotEmpty())
                    ->schema([
                        ViewEntry::make('jasa_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.penjualan-jasa-table')
                            ->state(fn(Penjualan $record) => $record->jasaItems()->with([
                                'jasa',
                                'pembelianItem.pembelian',
                                'pembelianItem.produk',
                                'pembelianJasa.pembelian',
                                'pembelianJasa.jasa',
                            ])->get()),
                    ]),

                // === BAGIAN RINGKASAN PEMBAYARAN (SPLIT MATCHING PEMBELIAN) ===
                InfoSection::make()
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('total_tagihan')
                                    ->label('Total Tagihan')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->state(fn(Penjualan $record) => static::calculateGrandTotal($record)),

                                TextEntry::make('total_dibayar')
                                    ->label('Total Dibayar')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('success')
                                    ->state(fn(Penjualan $record) => $record->pembayaran->sum('jumlah')),
                            ]),

                            InfoGroup::make([
                                TextEntry::make('sisa_bayar')
                                    ->label('Sisa Bayar')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('danger')
                                    ->state(function (Penjualan $record) {
                                        $grandTotal = static::calculateGrandTotal($record);
                                        $paid = $record->pembayaran->sum('jumlah');

                                        return max(0, $grandTotal - $paid);
                                    }),

                                TextEntry::make('kembalian')
                                    ->label('Kembalian / Kelebihan')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('info')
                                    ->state(function (Penjualan $record) {
                                        $grandTotal = static::calculateGrandTotal($record);
                                        $paid = $record->pembayaran->sum('jumlah');

                                        return max(0, $paid - $grandTotal);
                                    }),
                            ]),
                        ])->from('md'),
                    ]),

                // === RINCIAN PEMBAYARAN DETAIL (TABLE) ===
                InfoSection::make('Rincian Pembayaran')
                    ->schema([
                        RepeatableEntry::make('pembayaran')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y'),
                                TextEntry::make('metode_bayar')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        default => $state,
                                    })
                                    ->color('primary'),
                                TextEntry::make('akunTransaksi.nama_akun')
                                    ->label('Akun')
                                    ->icon('heroicon-m-building-library')
                                    ->placeholder('-'),
                                TextEntry::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible()
                    ->collapsed(fn(Penjualan $record) => $record->pembayaran->isEmpty()),

                // === FOOTER: CATATAN ===
                InfoSection::make('Catatan')
                    ->visible(fn(Penjualan $record) => ! empty($record->catatan))
                    ->schema([
                        TextEntry::make('catatan')
                            ->hiddenLabel()
                            ->markdown()
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->collapsible(),

                InfoSection::make('Bukti & Dokumentasi')
                    ->icon('heroicon-o-camera')
                    ->visible(fn(Penjualan $record) => $record->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty() || ! empty($record->foto_dokumen))
                    ->schema([
                        ViewEntry::make('all_photos_gallery')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.penjualan-photos-gallery')
                            ->state(fn(Penjualan $record) => [
                                'bukti_pembayaran' => $record->pembayaran->whereNotNull('bukti_transfer')->pluck('bukti_transfer')->toArray(),
                                'foto_dokumen' => $record->foto_dokumen ?? [],
                            ]),
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

    /**
     * Get condition options for a product based on available batches.
     */
    public static function getConditionOptions(int $productId): array
    {
        if ($productId < 1) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->pluck('kondisi')
            ->filter()
            ->unique()
            ->mapWithKeys(fn(string $condition): array => [$condition => ucfirst(strtolower($condition))])
            ->toArray();
    }

    /**
     * Get the oldest available batch for a product.
     */
    public static function getOldestAvailableBatch(int $productId, ?string $condition = null): ?PembelianItem
    {
        if ($productId < 1) {
            return null;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when($condition, fn($query) => $query->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->first();
    }

    /**
     * Get available stock quantity for a product.
     */
    public static function getAvailableQty(int $productId, ?string $condition = null): int
    {
        if ($productId < 1) {
            return 0;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        $query = PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0);

        if ($condition) {
            $query->where('kondisi', $condition);
        }

        return (int) $query->sum($qtyColumn);
    }

    protected static function formatCurrency(int $value): string
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}
