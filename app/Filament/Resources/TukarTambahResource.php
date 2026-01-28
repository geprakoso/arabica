<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TukarTambahResource\Pages;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PembelianRelationManager;
use App\Filament\Resources\TukarTambahResource\RelationManagers\PenjualanRelationManager;
use App\Models\AkunTransaksi;
use App\Models\Jasa;
use App\Models\Member;
use App\Models\Pembelian;
use App\Models\PembelianItem;
use App\Models\Penjualan;
use App\Models\Supplier;
use App\Models\TukarTambah;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
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
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TukarTambahResource extends BaseResource
{
    protected static ?string $model = TukarTambah::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Tukar Tambah';

    protected static ?string $pluralLabel = 'Tukar Tambah';

    protected static ?string $navigationParentItem = 'Input Penjualan';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Transaksi')
                    ->description('Detail transaksi tukar tambah barang')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('no_nota')
                                    ->label('No. Nota Utama')
                                    ->default(fn () => TukarTambah::generateNoNota())
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->columnSpan(1),
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->displayFormat('d F Y')
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->prefixIcon('heroicon-m-calendar')
                                    ->columnSpan(1),
                                Select::make('id_karyawan')
                                    ->label('PJ Transaksi')
                                    ->relationship('karyawan', 'nama_karyawan')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => Auth::user()?->karyawan?->id)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->prefixIcon('heroicon-m-user')
                                    ->columnSpan(1),
                                Select::make('id_member')
                                    ->label('Pelanggan')
                                    ->options(fn () => Member::query()
                                        ->orderBy('nama_member')
                                        ->pluck('nama_member', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->prefixIcon('heroicon-m-user')
                                    ->createOptionModalHeading('Tambah Member')
                                    ->createOptionAction(fn ($action) => $action->label('Tambah Member'))
                                    ->createOptionForm([
                                        TextInput::make('nama_member')
                                            ->label('Nama Lengkap')
                                            ->required()
                                            ->validationMessages([
                                                'required' => 'Perlu diisi',
                                            ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('no_hp')
                                                ->label('Nomor WhatsApp / HP')
                                                ->tel()
                                                ->required()
                                                ->validationMessages([
                                                    'required' => 'Perlu diisi',
                                                ])
                                                ->unique(table: (new Member)->getTable(), column: 'no_hp'),

                                            TextInput::make('email')
                                                ->label('Alamat Email')
                                                ->email()
                                                ->nullable(),
                                        ]),
                                    ])
                                    ->createOptionUsing(fn (array $data): int => (int) Member::query()->create($data)->getKey())
                                    ->columnSpan(1),

                            ]),
                        Section::make()
                            ->heading('📝 Catatan Tambahan')
                            ->schema([
                                Textarea::make('catatan')
                                    ->label('Catatan Umum')
                                    ->rows(2)
                                    ->placeholder('Keterangan tambahan...')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->collapsed(true)
                            ->compact(),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Tabs::make('Input Detail')
                    ->tabs([
                        // TAB PENJUALAN
                        Tab::make('Barang Keluar (Penjualan)')
                            ->icon('heroicon-m-arrow-up-tray')
                            ->schema([
                                Group::make()
                                    ->statePath('penjualan')
                                    ->schema([
                                        // Section::make('Data Penjualan')
                                        //     ->description('Informasi pelanggan dan sales')
                                        //     ->icon('heroicon-m-user-group')
                                        //     ->schema([
                                        //         Grid::make(2)
                                        //             ->schema([
                                        //             ]),
                                        //     ])
                                        //     ->compact(),

                                        TableRepeater::make('items')
                                            ->label('Daftar Barang Keluar')
                                            ->addActionLabel('+ Tambah Barang')
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                // Update total items count
                                                $items = $get('items') ?? [];
                                                $jasaItems = $get('jasa_items') ?? [];
                                                $totalItems = count($items) + count($jasaItems);
                                                $set('total_items_summary', $totalItems);

                                                // Update total price
                                                $productTotal = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga_jual'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $serviceTotal = collect($jasaItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $total = $productTotal + $serviceTotal;
                                                $set('total_price_summary', number_format($total, 0, ',', '.'));

                                                // Update grand total - use absolute paths
                                                $pembelianItems = $get('../../pembelian/items') ?? [];
                                                $pembelianTotal = collect($pembelianItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $hpp = (int) ($item['hpp'] ?? 0);

                                                    return $qty * $hpp;
                                                });
                                                $grandTotal = $total - $pembelianTotal;
                                                // Set at root level using absolute path traversal
                                                $set('../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
                                            })
                                            ->schema([
                                                \Filament\Forms\Components\Hidden::make('id_pembelian_item'),
                                                Select::make('id_produk')
                                                    ->label('Produk')
                                                    ->options(function (Get $get): array {
                                                        $options = \App\Filament\Resources\PenjualanResource::getAvailableProductOptions();
                                                        $currentId = $get('id_produk');

                                                        if ($currentId && ! array_key_exists($currentId, $options)) {
                                                            $product = \App\Models\Produk::find($currentId);
                                                            if ($product) {
                                                                $options[$currentId] = $product->nama_produk.' (Stok Habis)';
                                                            }
                                                        }

                                                        return $options;
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                        if ($state) {
                                                            // Get default price from oldest batch
                                                            $batch = \App\Filament\Resources\PenjualanResource::getOldestAvailableBatch($state);
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

                                                        return \App\Filament\Resources\PenjualanResource::getConditionOptions($productId);
                                                    })
                                                    ->native(false)
                                                    ->placeholder('Otomatis')
                                                    ->nullable()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, ?string $state, Get $get): void {
                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                        if ($productId > 0) {
                                                            // Get price for this condition
                                                            $batch = \App\Filament\Resources\PenjualanResource::getOldestAvailableBatch($productId, $state);
                                                            if ($batch) {
                                                                $set('harga_jual', $batch->harga_jual);
                                                                $set('hpp', $batch->hpp);
                                                            }
                                                        }
                                                    }),
                                                Hidden::make('_original_qty')
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function ($component, $state, Get $get) {
                                                        // Store the original qty when the form is loaded for editing
                                                        $qty = (int) ($get('qty') ?? 0);
                                                        $component->state($qty);
                                                    }),
                                                TextInput::make('qty')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->step(1)
                                                    ->minValue(1)
                                                    ->maxValue(function (Get $get): ?int {
                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                        if ($productId < 1) {
                                                            return null;
                                                        }
                                                        $condition = $get('kondisi');
                                                        $available = \App\Filament\Resources\PenjualanResource::getAvailableQty($productId, $condition);

                                                        // Add back the original qty if editing an existing item
                                                        $originalQty = (int) ($get('_original_qty') ?? 0);
                                                        $available += $originalQty;

                                                        return $available > 0 ? $available : null;
                                                    })
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->extraInputAttributes(function (Get $get): array {
                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                        $condition = $get('kondisi');
                                                        $max = $productId > 0
                                                            ? \App\Filament\Resources\PenjualanResource::getAvailableQty($productId, $condition)
                                                            : null;

                                                        // Add back the original qty if editing an existing item
                                                        if ($max !== null) {
                                                            $originalQty = (int) ($get('_original_qty') ?? 0);
                                                            $max += $originalQty;
                                                        }

                                                        return [
                                                            'min' => 1,
                                                            'max' => $max,
                                                            'step' => 1,
                                                        ];
                                                    })
                                                    ->placeholder(function (Get $get): string {
                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                        if ($productId < 1) {
                                                            return '';
                                                        }
                                                        $condition = $get('kondisi');
                                                        $available = \App\Filament\Resources\PenjualanResource::getAvailableQty($productId, $condition);

                                                        // Add back the original qty if editing an existing item
                                                        $originalQty = (int) ($get('_original_qty') ?? 0);
                                                        $available += $originalQty;

                                                        return 'Stok: '.number_format($available, 0, ',', '.');
                                                    })
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                        'min' => 'Minimal 1',
                                                        'max' => 'Stok tidak cukup! Maksimal :max unit.',
                                                    ])
                                                    ->afterStateUpdated(function (Set $set, Get $get, ?int $state): void {
                                                        // Ensure qty is within bounds
                                                        $qty = (int) ($state ?? 0);
                                                        $productId = (int) ($get('id_produk') ?? 0);
                                                        $condition = $get('kondisi');

                                                        if ($productId > 0) {
                                                            $available = \App\Filament\Resources\PenjualanResource::getAvailableQty($productId, $condition);

                                                            // Add back the original qty if editing an existing item
                                                            $originalQty = (int) ($get('_original_qty') ?? 0);
                                                            $available += $originalQty;

                                                            // Clamp qty to min=1, max=available
                                                            if ($qty < 1 && $qty !== 0) {
                                                                $set('qty', 1);
                                                            } elseif ($qty > $available && $available > 0) {
                                                                $set('qty', $available);
                                                            }
                                                        }

                                                        // Adjust serials array to match qty
                                                        $qty = (int) ($get('qty') ?? 0);
                                                        $serials = $get('serials') ?? [];
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
                                                    ->dehydrated(true)
                                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                                        // If state is already filled, do nothing
                                                        if (! empty($state)) {
                                                            return;
                                                        }

                                                        // Attempt to fetch from database record if available
                                                        if ($record instanceof \App\Models\PenjualanItem) {
                                                            if ($record->hpp > 0) {
                                                                $component->state($record->hpp);

                                                                return;
                                                            }

                                                            // If still empty, try to get from batch (pembelian item)
                                                            if ($record->id_pembelian_item) {
                                                                $batch = \App\Models\PembelianItem::find($record->id_pembelian_item);
                                                                if ($batch) {
                                                                    $component->state($batch->hpp);
                                                                }
                                                            }
                                                        }
                                                    }),

                                                TextInput::make('harga_jual')
                                                    ->label('Harga Satuan')
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->reactive(),

                                                Hidden::make('serials')
                                                    ->default([])
                                                    ->reactive(),

                                                TextInput::make('serials_count')
                                                    ->label('Serial Number & Garansi')
                                                    ->formatStateUsing(fn (Get $get): string => count($get('serials') ?? []).' serials')
                                                    ->live()
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->suffixAction(
                                                        FormAction::make('manage_serials')
                                                            ->label('Manage')
                                                            ->icon('heroicon-o-qr-code')
                                                            ->button()
                                                            ->color('info')
                                                            ->modalHeading('Manage Serial Numbers')
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
                                                                    $serials[] = [
                                                                        'sn' => '',
                                                                        'garansi' => '',
                                                                    ];
                                                                }

                                                                return ['serials_temp' => $serials];
                                                            })
                                                            ->form([
                                                                TableRepeater::make('serials_temp')
                                                                    ->label('')
                                                                    ->schema([
                                                                        TextInput::make('sn')
                                                                            ->label('Serial Number')
                                                                            ->required()
                                                                            ->validationMessages([
                                                                                'required' => 'Perlu diisi',
                                                                            ]),
                                                                        TextInput::make('garansi')
                                                                            ->label('Garansi'),
                                                                    ])
                                                                    ->defaultItems(0)
                                                                    ->addActionLabel('+ Add Serial')
                                                                    ->reorderable(false)
                                                                    ->colStyles([
                                                                        'sn' => 'width: 60%;',
                                                                        'garansi' => 'width: 40%;',
                                                                    ]),
                                                            ])
                                                            ->action(function (Set $set, array $data, $livewire): void {
                                                                $set('serials', $data['serials_temp'] ?? []);
                                                            })
                                                            ->after(function (Set $set, Get $get): void {
                                                                // Force refresh of serials_count by updating it
                                                                $serials = $get('serials') ?? [];
                                                                $set('serials_count', count($serials));
                                                            })
                                                    ),
                                            ])
                                            ->colStyles([
                                                'id_produk' => 'width: 30%;',
                                                'kondisi' => 'width: 10%;',
                                                'qty' => 'width: 10%;',
                                                'hpp' => 'width: 15%;',
                                                'harga_jual' => 'width: 15%;',
                                            ])
                                            ->defaultItems(1)
                                            ->reorderable(false)
                                            ->columns(1),

                                        TableRepeater::make('jasa_items')
                                            ->label('Layanan Jasa (Opsional)')
                                            ->addActionLabel('+ Tambah Jasa')
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                // Update total items count
                                                $items = $get('items') ?? [];
                                                $jasaItems = $get('jasa_items') ?? [];
                                                $totalItems = count($items) + count($jasaItems);
                                                $set('total_items_summary', $totalItems);

                                                // Update total price
                                                $productTotal = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga_jual'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $serviceTotal = collect($jasaItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $total = $productTotal + $serviceTotal;
                                                $set('total_price_summary', number_format($total, 0, ',', '.'));

                                                // Update grand total - recalculate pembelian from items
                                                $pembelianItems = $get('../../../pembelian/items') ?? [];
                                                $pembelianTotal = collect($pembelianItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $hpp = (int) ($item['hpp'] ?? 0);

                                                    return $qty * $hpp;
                                                });
                                                $grandTotal = $total - $pembelianTotal;
                                                // Set at root level - jasa_items is nested deeper
                                                $set('../../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
                                            })
                                            ->schema([
                                                Select::make('jasa_id')
                                                    ->label('Jasa')
                                                    ->prefixIcon('hugeicons-tools')
                                                    ->options(fn () => Jasa::query()->orderBy('nama_jasa')->pluck('nama_jasa', 'id')->all())
                                                    ->searchable()
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->reactive()
                                                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                                                        $set('harga', $state ? (int) (Jasa::query()->find($state)?->harga ?? 0) : null);
                                                    })
                                                    ->columnSpan(2),
                                                TextInput::make('qty')
                                                    ->label('Jml')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->reactive(),
                                                TextInput::make('harga')
                                                    ->label('Tarif')
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->reactive(),
                                            ])
                                            ->colStyles([
                                                'jasa_id' => 'width: 60%;',
                                                'qty' => 'width: 15%;',
                                                'harga' => 'width: 25%;',
                                            ])
                                            ->columns(3)
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->cloneable(),

                                        // Summary Section
                                        Grid::make(1)
                                            ->schema([

                                                TextInput::make('total_price_summary')
                                                    ->label('Total Harga')
                                                    ->prefix('Rp')
                                                    ->live()
                                                    ->default(0)
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (Set $set, Get $get): void {
                                                        $items = $get('items') ?? [];
                                                        $jasaItems = $get('jasa_items') ?? [];

                                                        // Calculate product total: qty * harga_jual
                                                        $productTotal = collect($items)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga_jual'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        // Calculate service total: qty * harga
                                                        $serviceTotal = collect($jasaItems)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        $total = $productTotal + $serviceTotal;
                                                        $set('total_price_summary', number_format($total, 0, ',', '.'));
                                                    })
                                                    ->suffixIcon('heroicon-m-banknotes'),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // TAB PEMBELIAN
                        Tab::make('Barang Masuk (Pembelian)')
                            ->icon('heroicon-m-arrow-down-tray')
                            ->schema([
                                Group::make()
                                    ->statePath('pembelian')
                                    ->schema([
                                        Hidden::make('id_supplier')
                                            ->default(function () {
                                                // Create or get 'User Jual' supplier
                                                $supplier = Supplier::query()
                                                    ->where('nama_supplier', 'User Jual')
                                                    ->first();

                                                if (! $supplier) {
                                                    $supplier = Supplier::query()->create([
                                                        'nama_supplier' => 'User Jual',
                                                        'no_hp' => '0000',
                                                    ]);
                                                }

                                                return $supplier->id;
                                            })
                                            ->dehydrated(),

                                        TableRepeater::make('items')
                                            ->label('Barang')
                                            ->addActionLabel('+ Tambah Barang')
                                            ->minItems(1)
                                            ->reactive()
                                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                                // Recalculate total pembelian
                                                $items = $get('items') ?? [];

                                                $total = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $hpp = (int) ($item['hpp'] ?? 0);

                                                    return $qty * $hpp;
                                                });

                                                $set('total_pembelian_summary', number_format($total, 0, ',', '.'));

                                                // Update grand total - recalculate penjualan from items
                                                $penjualanItems = $get('../../penjualan/items') ?? [];
                                                $penjualanJasaItems = $get('../../penjualan/jasa_items') ?? [];

                                                $productTotal = collect($penjualanItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga_jual'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $serviceTotal = collect($penjualanJasaItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $penjualanTotal = $productTotal + $serviceTotal;
                                                $grandTotal = $penjualanTotal - $total;
                                                // Set at root level - pembelian items is nested: pembelian.items
                                                $set('../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
                                            })
                                            ->schema([
                                                \Filament\Forms\Components\Hidden::make('id_pembelian_item'),
                                                Select::make('id_produk')
                                                    ->label('Produk')
                                                    ->options(fn () => \App\Models\Produk::query()->orderBy('nama_produk')->pluck('nama_produk', 'id')->all())
                                                    ->searchable()
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->columnSpan(2),
                                                Select::make('kondisi')
                                                    ->label('Kondisi')
                                                    ->options(['baru' => 'Baru', 'bekas' => 'Bekas'])
                                                    ->default('baru')
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ]),
                                                TextInput::make('qty')
                                                    ->label('Jml')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->lazy()
                                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                                        // Trigger parent repeater update
                                                        $items = $get('../../items') ?? [];
                                                        $total = collect($items)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $hpp = (int) ($item['hpp'] ?? 0);

                                                            return $qty * $hpp;
                                                        });
                                                        $set('../../total_pembelian_summary', number_format($total, 0, ',', '.'));

                                                        // Update grand total - recalculate penjualan from items
                                                        $penjualanItems = $get('../../../../penjualan/items') ?? [];
                                                        $penjualanJasaItems = $get('../../../../penjualan/jasa_items') ?? [];

                                                        $productTotal = collect($penjualanItems)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga_jual'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        $serviceTotal = collect($penjualanJasaItems)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        $penjualanTotal = $productTotal + $serviceTotal;
                                                        $grandTotal = $penjualanTotal - $total;
                                                        // Set at root level
                                                        $set('../../../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
                                                    }),
                                                TextInput::make('hpp')
                                                    ->label('HPP (Beli)')
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ])
                                                    ->lazy()
                                                    ->afterStateUpdated(function (Set $set, Get $get): void {
                                                        // Trigger parent repeater update
                                                        $items = $get('../../items') ?? [];
                                                        $total = collect($items)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $hpp = (int) ($item['hpp'] ?? 0);

                                                            return $qty * $hpp;
                                                        });
                                                        $set('../../total_pembelian_summary', number_format($total, 0, ',', '.'));

                                                        // Update grand total - recalculate penjualan from items
                                                        $penjualanItems = $get('../../../../penjualan/items') ?? [];
                                                        $penjualanJasaItems = $get('../../../../penjualan/jasa_items') ?? [];

                                                        $productTotal = collect($penjualanItems)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga_jual'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        $serviceTotal = collect($penjualanJasaItems)->sum(function ($item) {
                                                            $qty = (int) ($item['qty'] ?? 0);
                                                            $price = (int) ($item['harga'] ?? 0);

                                                            return $qty * $price;
                                                        });

                                                        $penjualanTotal = $productTotal + $serviceTotal;
                                                        $grandTotal = $penjualanTotal - $total;
                                                        // Set at root level
                                                        $set('../../../../grand_total_tukar_tambah', number_format($grandTotal, 0, ',', '.'));
                                                    }),
                                                TextInput::make('harga_jual')
                                                    ->label('Rencana Jual')
                                                    ->prefix('Rp')
                                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                                    ->required()
                                                    ->validationMessages([
                                                        'required' => 'Perlu diisi',
                                                    ]),
                                            ])
                                            ->columns(6)
                                            ->colStyles([
                                                'id_produk' => 'width:37%',
                                                'kondisi' => 'width:15%',
                                                'qty' => 'width:8%',
                                                'hpp' => 'width:20%',
                                                'harga_jual' => 'width:25%',
                                            ]),

                                        // Summary for Pembelian
                                        TextInput::make('total_pembelian_summary')
                                            ->label('Total Pembelian')
                                            ->prefix('Rp')
                                            ->live()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->afterStateHydrated(function (Set $set, Get $get): void {
                                                $items = $get('items') ?? [];

                                                // Calculate total: qty * hpp
                                                $total = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $hpp = (int) ($item['hpp'] ?? 0);

                                                    return $qty * $hpp;
                                                });

                                                $set('total_pembelian_summary', number_format($total, 0, ',', '.'));
                                            })
                                            ->suffixIcon('heroicon-m-calculator'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Grand Total Section (at root level to access both penjualan and pembelian)
                Section::make('Grand Total Tukar Tambah')
                    ->description('Selisih total penjualan dan pembelian')
                    ->icon('heroicon-m-calculator')
                    ->schema([
                        Placeholder::make('grand_total_tukar_tambah')
                            ->label('Grand Total (Penjualan - Pembelian)')
                            ->content(function (Get $get): string {
                                // Calculate Penjualan total from items
                                $penjualanItems = $get('penjualan.items') ?? [];
                                $penjualanJasaItems = $get('penjualan.jasa_items') ?? [];

                                $productTotal = collect($penjualanItems)->sum(function ($item) {
                                    $qty = (int) ($item['qty'] ?? 0);
                                    $price = (int) ($item['harga_jual'] ?? 0);

                                    return $qty * $price;
                                });

                                $serviceTotal = collect($penjualanJasaItems)->sum(function ($item) {
                                    $qty = (int) ($item['qty'] ?? 0);
                                    $price = (int) ($item['harga'] ?? 0);

                                    return $qty * $price;
                                });

                                $penjualanTotal = $productTotal + $serviceTotal;

                                // Calculate Pembelian total from items
                                $pembelianItems = $get('pembelian.items') ?? [];
                                $pembelianTotal = collect($pembelianItems)->sum(function ($item) {
                                    $qty = (int) ($item['qty'] ?? 0);
                                    $hpp = (int) ($item['hpp'] ?? 0);

                                    return $qty * $hpp;
                                });

                                // Calculate grand total
                                $grandTotal = $penjualanTotal - $pembelianTotal;

                                return 'Rp '.number_format($grandTotal, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600'])
                            ->helperText('Total yang dibayar pelanggan setelah dikurangi nilai barang masuk'),
                    ])
                    ->collapsed(false),

                // Unified Pembayaran Section
                Section::make('Pembayaran')
                    ->description('Pembayaran untuk penjualan dan pembelian')
                    ->icon('heroicon-m-banknotes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('penjualan.diskon_total')
                                    ->label('Diskon Penjualan')
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->default(0)
                                    ->helperText('Diskon untuk barang keluar'),
                                Select::make('pembelian.tipe_pembelian')
                                    ->label('Pajak Pembelian')
                                    ->options(['non_ppn' => 'Non PPN', 'ppn' => 'PPN (11%)'])
                                    ->default('non_ppn')
                                    ->helperText('Pajak untuk barang masuk'),
                            ]),

                        TableRepeater::make('unified_pembayaran')
                            ->label('Metode Pembayaran')
                            ->addActionLabel('+ Tambah Pembayaran')
                            ->schema([
                                DatePicker::make('tanggal')
                                    ->label('Tanggal')
                                    ->default(now())
                                    ->native(false)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ]),
                                Select::make('tipe_transaksi')
                                    ->label('Untuk')
                                    ->options([
                                        'penjualan' => 'Penjualan',
                                        'pembelian' => 'Pembelian',
                                    ])
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->reactive(),

                                Select::make('metode_bayar')
                                    ->label('Metode')
                                    ->options(['cash' => 'Tunai', 'transfer' => 'Transfer'])
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->reactive(),
                                Select::make('akun_transaksi_id')
                                    ->label('Akun Transaksi')
                                    ->options(fn () => AkunTransaksi::query()->where('is_active', true)->pluck('nama_akun', 'id'))
                                    ->searchable()
                                    ->required(fn (Get $get) => $get('metode_bayar') === 'transfer')
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ]),
                                TextInput::make('jumlah')
                                    ->label('Nominal')
                                    ->prefix('Rp')
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 0)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Perlu diisi',
                                    ])
                                    ->placeholder(function (Get $get, $livewire): string {
                                        $tipeTransaksi = $get('tipe_transaksi');

                                        if (! $tipeTransaksi) {
                                            return 'Pilih tipe transaksi dulu';
                                        }

                                        try {
                                            $formData = $livewire->data ?? [];

                                            if ($tipeTransaksi === 'penjualan') {
                                                // Calculate penjualan total from form data
                                                $items = $formData['penjualan']['items'] ?? [];
                                                $jasaItems = $formData['penjualan']['jasa_items'] ?? [];

                                                $productTotal = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga_jual'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $serviceTotal = collect($jasaItems)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $price = (int) ($item['harga'] ?? 0);

                                                    return $qty * $price;
                                                });

                                                $diskon = (int) ($formData['penjualan']['diskon_total'] ?? 0);
                                                $total = max(0, ($productTotal + $serviceTotal) - $diskon);

                                                return $total > 0 ? 'Saran: Rp '.number_format($total, 0, ',', '.') : 'Total Penjualan';
                                            } elseif ($tipeTransaksi === 'pembelian') {
                                                // Calculate pembelian total from form data
                                                $items = $formData['pembelian']['items'] ?? [];

                                                $total = collect($items)->sum(function ($item) {
                                                    $qty = (int) ($item['qty'] ?? 0);
                                                    $hpp = (int) ($item['hpp'] ?? 0);

                                                    return $qty * $hpp;
                                                });

                                                return $total > 0 ? 'Saran: Rp '.number_format($total, 0, ',', '.') : 'Total Pembelian';
                                            }
                                        } catch (\Exception $e) {
                                            // Fallback if form data is not available
                                            return $tipeTransaksi === 'penjualan' ? 'Total Penjualan' : 'Total Pembelian';
                                        }

                                        return 'Masukkan nominal';
                                    })
                                    ->live(onBlur: true),
                                FileUpload::make('bukti_transfer')
                                    ->label('Bukti')
                                    ->image()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('tukar-tambah/bukti-transfer')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->saveUploadedFileUsing(function ($file, $get) {
                                        $directory = 'tukar-tambah/bukti-transfer';
                                        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                        $filename = \Illuminate\Support\Str::slug($filename).'-'.uniqid().'.webp';

                                        // Create temp path for processed image
                                        $tempPath = sys_get_temp_dir().'/'.$filename;

                                        // Get image dimensions
                                        $imageInfo = getimagesize($file->getRealPath());
                                        $width = $imageInfo[0] ?? 0;
                                        $height = $imageInfo[1] ?? 0;

                                        // FHD limits
                                        $maxWidth = 1920;
                                        $maxHeight = 1080;

                                        // Use Spatie Image
                                        $image = \Spatie\Image\Image::load($file->getRealPath());

                                        // Only resize if image is larger than FHD
                                        if ($width > $maxWidth || $height > $maxHeight) {
                                            $image->width($maxWidth)
                                                ->height($maxHeight)
                                                ->fit(\Spatie\Image\Enums\Fit::Contain);
                                        }

                                        // Convert to webp with 80% quality and save
                                        $image->format('webp')
                                            ->quality(80)
                                            ->save($tempPath);

                                        // Store to disk
                                        $path = $directory.'/'.$filename;
                                        \Illuminate\Support\Facades\Storage::disk('public')->put($path, file_get_contents($tempPath));

                                        // Cleanup temp file
                                        @unlink($tempPath);

                                        return $path;
                                    })
                                    ->openable()
                                    ->downloadable()
                                    ->previewable(false)
                                    ->extraAttributes(['class' => 'compact-file-upload'])
                                    ->helperText(new \Illuminate\Support\HtmlString('
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
                            ->colStyles([
                                'tanggal' => 'width: 13%;',
                                'tipe_transaksi' => 'width: 12%;',
                                'metode_bayar' => 'width: 18%;',
                                'akun_transaksi_id' => 'width: 18%;',
                                'jumlah' => 'width: 19%;',
                                'bukti_transfer' => 'width: 29%;',
                            ])
                            ->columns(6)
                            ->minItems(0)
                            ->defaultItems(2)
                            ->default([
                                [
                                    'tipe_transaksi' => 'penjualan',
                                    'tanggal' => now()->format('Y-m-d'),
                                    'metode_bayar' => null,
                                    'akun_transaksi_id' => null,
                                    'jumlah' => null,
                                    'bukti_transfer' => null,
                                ],
                                [
                                    'tipe_transaksi' => 'pembelian',
                                    'tanggal' => now()->format('Y-m-d'),
                                    'metode_bayar' => null,
                                    'akun_transaksi_id' => null,
                                    'jumlah' => null,
                                    'bukti_transfer' => null,
                                ],
                            ])
                            ->reorderable(false)
                            ->afterStateHydrated(function (Set $set, Get $get, $state) {
                                // On edit, load existing payments from both penjualan and pembelian
                                if (filled($state)) {
                                    return;
                                }

                                $unifiedPayments = [];

                                // Load penjualan payments
                                $penjualanPayments = $get('penjualan.pembayaran') ?? [];
                                foreach ($penjualanPayments as $payment) {
                                    $unifiedPayments[] = array_merge($payment, ['tipe_transaksi' => 'penjualan']);
                                }

                                // Load pembelian payments
                                $pembelianPayments = $get('pembelian.pembayaran') ?? [];
                                foreach ($pembelianPayments as $payment) {
                                    $unifiedPayments[] = array_merge($payment, ['tipe_transaksi' => 'pembelian']);
                                }

                                if (! empty($unifiedPayments)) {
                                    $set('unified_pembayaran', $unifiedPayments);
                                }
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_tukar_tambah')
                    ->label('Kode')
                    ->state(fn (TukarTambah $record): string => $record->kode)
                    ->weight('bold')
                    ->copyable()
                    ->sortable(),
                TextColumn::make('no_nota')
                    ->label('No. Nota')
                    ->icon('heroicon-m-document-text')
                    ->copyable()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('penjualan.member.nama_member')
                    ->label('Pelanggan')
                    ->icon('heroicon-m-user-circle')
                    ->searchable(['nama_member', 'no_hp'])
                    ->description(fn (TukarTambah $record): ?string => $record->penjualan?->member?->no_hp)
                    ->sortable(),
                TextColumn::make('karyawan.nama_karyawan')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user')
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('penjualan.no_nota')
                    ->label('Nota Penjualan')
                    ->icon('heroicon-m-receipt-percent')
                    ->copyable(),
                TextColumn::make('pembelian.no_po')
                    ->label('Nota Pembelian')
                    ->icon('heroicon-m-document-text')
                    ->copyable(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->actions([
                \Filament\Tables\Actions\ActionGroup::make([
                    Action::make('invoice')
                        ->label('Invoice')
                        ->icon('heroicon-m-printer')
                        ->color('primary')
                        ->url(fn (TukarTambah $record) => route('tukar-tambah.invoice', $record))
                        ->openUrlInNewTab(),
                    Action::make('invoice_simple')
                        ->label('Invoice Simple')
                        ->icon('heroicon-m-document-text')
                        ->color('gray')
                        ->url(fn (TukarTambah $record) => route('tukar-tambah.invoice.simple', $record))
                        ->openUrlInNewTab(),
                ])->label('Print')
                    ->icon('heroicon-m-printer')
                    ->color('primary'),
                \Filament\Tables\Actions\ActionGroup::make([

                    Action::make('view')
                        ->label('Lihat')
                        ->icon('heroicon-m-eye')
                        ->color('primary')
                        ->url(fn (TukarTambah $record) => TukarTambahResource::getUrl('view', ['record' => $record])),
                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-m-pencil-square')
                        ->color('warning')
                        ->action(function (TukarTambah $record, \Filament\Tables\Actions\Action $action): void {
                            $livewire = $action->getLivewire();
                            // if ($record->isEditLocked()) {
                            //     $livewire->editBlockedMessage = $record->getEditBlockedMessage();
                            //     $livewire->editBlockedPenjualanReferences = $record->getExternalPenjualanReferences()->all();
                            //     $livewire->replaceMountedAction('editBlocked');
                            //
                            //     return;
                            // }
                            $livewire->redirect(TukarTambahResource::getUrl('edit', ['record' => $record]));
                        }),

                ])->label('Aksi')
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->color('gray'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('delete')
                        ->label('Hapus')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Tukar Tambah')
                        ->modalDescription('Tukar tambah yang masih dipakai transaksi lain akan diblokir.')
                        ->action(function (Collection $records, \Filament\Tables\Actions\BulkAction $action): void {
                            $livewire = $action->getLivewire();
                            $failed = [];
                            $deleted = 0;
                            $blockedReferences = collect();

                            foreach ($records as $record) {
                                try {
                                    $record->delete();
                                    $deleted++;
                                } catch (ValidationException $exception) {
                                    $messages = collect($exception->errors())
                                        ->flatten()
                                        ->implode(' ');
                                    $failed[] = trim($messages) ?: 'Gagal menghapus tukar tambah.';
                                    $blockedReferences = $blockedReferences->merge($record->getExternalPenjualanReferences());
                                }
                            }

                            if (! empty($failed)) {
                                $livewire->deleteBlockedMessage = implode(' ', $failed);
                                $livewire->deleteBlockedPenjualanReferences = $blockedReferences
                                    ->unique('id')
                                    ->values()
                                    ->all();
                                $livewire->replaceMountedAction('bulkDeleteBlocked');
                            }

                            if ($deleted > 0) {
                                Notification::make()
                                    ->title('Tukar tambah dihapus')
                                    ->body('Berhasil menghapus '.$deleted.' data.')
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->filters([
                \Filament\Tables\Filters\Filter::make('periode')
                    ->form([
                        Grid::make(2)->schema([
                            Select::make('range')
                                ->label('Rentang Waktu')
                                ->options([
                                    'hari_ini' => 'Hari Ini',
                                    'kemarin' => 'Kemarin',
                                    '2_hari_lalu' => '2 Hari Lalu',
                                    '3_hari_lalu' => '3 Hari Lalu',
                                    'custom' => 'Custom',
                                ])
                                // ->default('hari_ini')
                                ->native(false)
                                ->reactive()
                                ->columnSpan(2),
                            DatePicker::make('from')
                                ->label('Mulai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('heroicon-m-calendar')
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                            DatePicker::make('until')
                                ->label('Sampai')
                                ->native(false)
                                ->placeholder('Pilih tanggal')
                                ->prefixIcon('heroicon-m-calendar')
                                ->hidden(fn (Get $get) => $get('range') !== 'custom'),
                        ]),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        $range = $data['range'] ?? null;

                        // If no range selected, return unfiltered query
                        if (! $range) {
                            return $query;
                        }

                        // Handle defaults cleanly
                        if ($range === 'hari_ini') {
                            return $query->whereDate('tanggal', now());
                        }

                        $startDate = null;
                        $endDate = now();

                        if ($range === 'custom') {
                            $startDate = $data['from'] ?? null;
                            $endDate = $data['until'] ?? null;

                            return $query
                                ->when(
                                    $startDate,
                                    fn (\Illuminate\Database\Eloquent\Builder $query, $date) => $query->whereDate('tanggal', '>=', $date),
                                )
                                ->when(
                                    $endDate,
                                    fn (\Illuminate\Database\Eloquent\Builder $query, $date) => $query->whereDate('tanggal', '<=', $date),
                                );
                        }

                        // Strict single day filtering for presets
                        $targetDate = match ($range) {
                            'kemarin' => now()->subDay(),
                            '2_hari_lalu' => now()->subDays(2),
                            '3_hari_lalu' => now()->subDays(3),
                            default => null,
                        };

                        return $query->when(
                            $targetDate,
                            fn (\Illuminate\Database\Eloquent\Builder $query, $date) => $query->whereDate('tanggal', $date)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $range = $data['range'] ?? null;
                        if (! $range) {
                            return null;
                        }

                        if ($range === 'custom') {
                            $from = $data['from'] ?? null;
                            $until = $data['until'] ?? null;

                            if (! $from && ! $until) {
                                return null;
                            }

                            $label = 'Periode: ';
                            if ($from) {
                                $label .= \Carbon\Carbon::parse($from)->translatedFormat('d M Y');
                            }
                            if ($until) {
                                $label .= ' s/d '.\Carbon\Carbon::parse($until)->translatedFormat('d M Y');
                            }

                            return $label;
                        }

                        $labels = [
                            'hari_ini' => 'Hari Ini',
                            'kemarin' => 'Kemarin',
                            '2_hari_lalu' => '2 Hari Lalu',
                            '3_hari_lalu' => '3 Hari Lalu',
                        ];

                        return isset($labels[$range]) ? 'Periode: '.$labels[$range] : null;
                    }),
            ])
            ->searchable()
            ->persistSearchInSession()
            ->searchPlaceholder('Cari No. Nota, Pelanggan, atau No. HP...')
            ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                // Add eager loading for search performance
                return $query->with(['member']);
            });
    }

    protected static function getAvailableConditionOptions(int $productId): array
    {
        if ($productId < 1) {
            return [];
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();
        $labels = [
            'baru' => 'Baru',
            'bekas' => 'Bekas',
        ];

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->whereNotNull('kondisi')
            ->distinct()
            ->orderBy('kondisi')
            ->pluck('kondisi')
            ->mapWithKeys(fn (string $value): array => [$value => $labels[$value] ?? ucfirst($value)])
            ->all();
    }

    protected static function getAvailableQty(int $productId, ?string $condition): int
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

    protected static function getDefaultPriceForProduct(?int $productId, ?string $condition = null): ?int
    {
        $batch = self::getOldestAvailableBatch($productId, $condition);

        return $batch?->harga_jual;
    }

    protected static function getOldestAvailableBatch(?int $productId, ?string $condition = null): ?PembelianItem
    {
        if (! $productId) {
            return null;
        }

        $qtyColumn = PembelianItem::qtySisaColumn();
        $productColumn = PembelianItem::productForeignKey();

        return PembelianItem::query()
            ->where($productColumn, $productId)
            ->where($qtyColumn, '>', 0)
            ->when($condition, fn ($query, $condition) => $query->where('kondisi', $condition))
            ->orderBy('id_pembelian_item')
            ->first();
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
                                TextEntry::make('id_tukar_tambah')
                                    ->label('Kode')
                                    ->state(fn (TukarTambah $record): string => $record->kode)
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->icon('heroicon-m-arrows-right-left'),
                                TextEntry::make('tanggal')
                                    ->label('Tanggal Transaksi')
                                    ->date('d F Y')
                                    ->icon('heroicon-m-calendar-days')
                                    ->color('gray'),
                            ]),

                            // Tengah: Pelanggan & Karyawan
                            InfoGroup::make([
                                TextEntry::make('penjualan.member.nama_member')
                                    ->label('Pelanggan')
                                    ->icon('heroicon-m-user-circle')
                                    ->color('primary')
                                    ->placeholder('-'),
                                TextEntry::make('karyawan.nama_karyawan')
                                    ->label('Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->placeholder('-'),
                            ]),

                            // Kanan: Linked Notas
                            InfoGroup::make([
                                TextEntry::make('penjualan.no_nota')
                                    ->label('Nota Penjualan')
                                    ->icon('heroicon-m-receipt-percent')
                                    ->url(fn (TukarTambah $record) => $record->penjualan
                                        ? PenjualanResource::getUrl('view', ['record' => $record->penjualan])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                                TextEntry::make('pembelian.no_po')
                                    ->label('Nota Pembelian')
                                    ->icon('heroicon-m-document-text')
                                    ->url(fn (TukarTambah $record) => $record->pembelian
                                        ? PembelianResource::getUrl('view', ['record' => $record->pembelian])
                                        : null)
                                    ->openUrlInNewTab()
                                    ->placeholder('-'),
                            ])->grow(false),
                        ])->from('md'),
                    ]),

                // === DAFTAR BARANG KELUAR (PENJUALAN ITEMS) ===
                InfoSection::make('Daftar Barang Keluar')
                    ->description('Item yang dijual ke pelanggan')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (TukarTambah $record) => $record->penjualan?->items->isNotEmpty())
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('penjualan_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.tukar-tambah-barang-keluar-table')
                            ->state(fn (TukarTambah $record) => $record->penjualan?->items->load('produk') ?? collect()),
                    ]),

                // === DAFTAR JASA (PENJUALAN JASA ITEMS) ===
                InfoSection::make('Daftar Jasa')
                    ->icon('hugeicons-tools')
                    ->visible(fn (TukarTambah $record) => $record->penjualan?->jasaItems->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('penjualan.jasaItems')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('jasa.nama_jasa')
                                    ->label('Jasa')
                                    ->weight(FontWeight::Medium),
                                TextEntry::make('qty')
                                    ->label('Qty')
                                    ->extraAttributes(['class' => 'text-center']),
                                TextEntry::make('harga')
                                    ->label('Harga')
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp '),
                                TextEntry::make('subtotal')
                                    ->label('Subtotal')
                                    ->state(fn ($record) => ($record->qty ?? 0) * ($record->harga ?? 0))
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('catatan')
                                    ->label('Catatan')
                                    ->placeholder('-'),
                            ])
                            ->columns(5),
                    ]),

                // === DAFTAR BARANG MASUK (PEMBELIAN ITEMS) ===
                InfoSection::make('Daftar Barang Masuk')
                    ->description('Item yang dibeli dari pelanggan')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (TukarTambah $record) => $record->pembelian?->items->isNotEmpty())
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('pembelian_items_table')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.tukar-tambah-barang-masuk-table')
                            ->state(fn (TukarTambah $record) => $record->pembelian?->items->load('produk') ?? collect()),
                    ]),

                // === RINGKASAN PEMBAYARAN (SPLIT LAYOUT) ===
                InfoSection::make('Ringkasan Pembayaran')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Split::make([
                            InfoGroup::make([
                                TextEntry::make('total_penjualan')
                                    ->label('Total Penjualan')
                                    ->state(function (TukarTambah $record): float {
                                        $penjualan = $record->penjualan;
                                        if (! $penjualan) {
                                            return 0;
                                        }
                                        $productTotal = $penjualan->items->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga_jual ?? 0));
                                        $serviceTotal = $penjualan->jasaItems->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga ?? 0));
                                        $diskon = (int) ($penjualan->diskon_total ?? 0);

                                        return max(0, ($productTotal + $serviceTotal) - $diskon);
                                    })
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('success'),

                                TextEntry::make('total_pembelian')
                                    ->label('Total Pembelian')
                                    ->state(function (TukarTambah $record): float {
                                        $pembelian = $record->pembelian;
                                        if (! $pembelian) {
                                            return 0;
                                        }

                                        return $pembelian->items->sum(fn ($item) => ($item->qty ?? 0) * ($item->hpp ?? 0));
                                    })
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('warning'),
                            ]),

                            InfoGroup::make([
                                TextEntry::make('grand_total')
                                    ->label('Grand Total (Penjualan - Pembelian)')
                                    ->state(function (TukarTambah $record): float {
                                        $penjualan = $record->penjualan;
                                        $pembelian = $record->pembelian;

                                        $penjualanTotal = 0;
                                        if ($penjualan) {
                                            $productTotal = $penjualan->items->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga_jual ?? 0));
                                            $serviceTotal = $penjualan->jasaItems->sum(fn ($item) => ($item->qty ?? 0) * ($item->harga ?? 0));
                                            $diskon = (int) ($penjualan->diskon_total ?? 0);
                                            $penjualanTotal = max(0, ($productTotal + $serviceTotal) - $diskon);
                                        }

                                        $pembelianTotal = 0;
                                        if ($pembelian) {
                                            $pembelianTotal = $pembelian->items->sum(fn ($item) => ($item->qty ?? 0) * ($item->hpp ?? 0));
                                        }

                                        return $penjualanTotal - $pembelianTotal;
                                    })
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->weight(FontWeight::Bold)
                                    ->size(TextEntrySize::Large)
                                    ->color('primary'),

                                TextEntry::make('total_dibayar_penjualan')
                                    ->label('Dibayar (Penjualan)')
                                    ->state(fn (TukarTambah $record): float => (float) ($record->penjualan?->pembayaran->sum('jumlah') ?? 0))
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->color('success'),

                                TextEntry::make('total_dibayar_pembelian')
                                    ->label('Dibayar (Pembelian)')
                                    ->state(fn (TukarTambah $record): float => (float) ($record->pembelian?->pembayaran->sum('jumlah') ?? 0))
                                    ->numeric(
                                        decimalPlaces: 0,
                                        decimalSeparator: ',',
                                        thousandsSeparator: '.',
                                    )
                                    ->prefix('Rp ')
                                    ->color('warning'),
                            ])->grow(false),
                        ])->from('md'),
                    ]),

                // === RINCIAN PEMBAYARAN PENJUALAN ===
                InfoSection::make('Rincian Pembayaran Penjualan')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (TukarTambah $record) => $record->penjualan?->pembayaran->isNotEmpty())
                    ->collapsible()
                    ->collapsed(fn (TukarTambah $record) => $record->penjualan?->pembayaran->isEmpty())
                    ->schema([
                        RepeatableEntry::make('penjualan.pembayaran')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y'),
                                TextEntry::make('metode_bayar')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                    ]),

                // === RINCIAN PEMBAYARAN PEMBELIAN ===
                InfoSection::make('Rincian Pembayaran Pembelian')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (TukarTambah $record) => $record->pembelian?->pembayaran->isNotEmpty())
                    ->collapsible()
                    ->collapsed(fn (TukarTambah $record) => $record->pembelian?->pembayaran->isEmpty())
                    ->schema([
                        RepeatableEntry::make('pembelian.pembayaran')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->date('d M Y'),
                                TextEntry::make('metode_bayar')
                                    ->label('Metode')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer',
                                        default => $state,
                                    })
                                    ->color('warning'),
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
                    ]),

                // === CATATAN ===
                InfoSection::make('Catatan')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (TukarTambah $record) => filled($record->catatan))
                    ->collapsible()
                    ->schema([
                        TextEntry::make('catatan')
                            ->hiddenLabel()
                            ->markdown()
                            ->prose(),
                    ]),

                // === BUKTI & DOKUMENTASI ===
                InfoSection::make('Bukti & Dokumentasi')
                    ->icon('heroicon-o-camera')
                    ->visible(fn (TukarTambah $record) => ! empty($record->foto_dokumen) ||
                        $record->penjualan?->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty() ||
                        $record->pembelian?->pembayaran->whereNotNull('bukti_transfer')->isNotEmpty()
                    )
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('all_photos_gallery')
                            ->hiddenLabel()
                            ->view('filament.infolists.components.tukar-tambah-photos-gallery')
                            ->state(fn (TukarTambah $record) => [
                                'foto_dokumen' => $record->foto_dokumen ?? [],
                                'bukti_penjualan' => $record->penjualan?->pembayaran->whereNotNull('bukti_transfer')->pluck('bukti_transfer')->toArray() ?? [],
                                'bukti_pembelian' => $record->pembelian?->pembayaran->whereNotNull('bukti_transfer')->pluck('bukti_transfer')->toArray() ?? [],
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTukarTambahs::route('/'),
            'create' => Pages\CreateTukarTambah::route('/create'),
            'view' => Pages\ViewTukarTambah::route('/{record}'),
            'edit' => Pages\EditTukarTambah::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PenjualanRelationManager::class,
            PembelianRelationManager::class,
        ];
    }
}
