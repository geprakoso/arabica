<?php

namespace App\Filament\Resources\Penjadwalan;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\MasterData\JasaResource;
use App\Filament\Resources\Penjadwalan\PenjadwalanServiceResource\Pages;
use App\Models\Member;
use App\Models\PenjadwalanService;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
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
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PenjadwalanServiceResource extends BaseResource
{
    protected static ?string $model = PenjadwalanService::class;

    protected static ?string $navigationIcon = 'hugeicons-service';

    protected static ?string $navigationGroup = 'Tugas';

    protected static ?string $navigationLabel = 'Penerimaan Service';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'no_resi';

    public static function getGloballySearchableAttributes(): array
    {
        return ['no_resi', 'member.nama_member', 'nama_perangkat', 'keluhan', 'technician.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Pelanggan' => $record->member->nama_member,
            'Perangkat' => $record->nama_perangkat,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormsGrid::make(3)
                    ->schema([
                        // --- KOLOM KIRI (DATA UTAMA) ---
                        FormsGroup::make()
                            ->schema([
                                // Section 1: Data Pelanggan
                                FormsSection::make('Informasi Pelanggan')
                                    ->description('Pilih pelanggan atau buat baru.')
                                    ->icon('hugeicons-user-circle')
                                    ->schema([
                                        Select::make('member_id')
                                            ->label('Pelanggan')
                                            ->relationship('member', 'nama_member')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (! $state) {
                                                    return;
                                                }
                                                $member = Member::find($state);
                                                $set('member_no_hp', $member?->no_hp);
                                                $set('member_alamat', $member?->alamat);
                                            })
                                            ->createOptionForm([
                                                TextInput::make('nama_member')->required()->label('Nama Lengkap'),
                                                TextInput::make('no_hp')->required()->label('No WhatsApp'),
                                                TextInput::make('alamat')->label('Alamat Domisili'),
                                            ])
                                            ->required()
                                            ->columnSpanFull(),

                                        Select::make('import_penjualan_id')
                                            ->label('Import dari Nota Penjualan')
                                            ->prefixIcon('heroicon-m-arrow-down-tray')
                                            ->searchable()
                                            ->options(fn () => \App\Models\Penjualan::latest('created_at')->limit(50)->pluck('no_nota', 'id_penjualan'))
                                            ->getSearchResultsUsing(fn (string $search) => \App\Models\Penjualan::where('no_nota', 'like', "%{$search}%")->limit(50)->pluck('no_nota', 'id_penjualan'))
                                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Penjualan::find($value)?->no_nota)
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if (! $state) {
                                                    return;
                                                }
                                                $penjualan = \App\Models\Penjualan::find($state);
                                                if ($penjualan && $penjualan->id_member) {
                                                    $set('member_id', $penjualan->id_member);

                                                    // Trigger update data member manual karena set() tidak mentrigger hook lain
                                                    $member = Member::find($penjualan->id_member);
                                                    $set('member_no_hp', $member?->no_hp);
                                                    $set('member_alamat', $member?->alamat);
                                                }
                                            })
                                            ->dehydrated(false)
                                            ->columnSpanFull()
                                            ->placeholder('Cari Nomor Nota...'),

                                        // Field Readonly (Tampil Rapi dengan Icon)
                                        FormsGrid::make(2)
                                            ->schema([
                                                TextInput::make('member_no_hp')
                                                    ->label('Kontak (Auto)')
                                                    ->prefixIcon('heroicon-m-phone')
                                                    ->disabled()
                                                    ->dehydrated(false),

                                                TextInput::make('member_alamat')
                                                    ->label('Alamat (Auto)')
                                                    ->prefixIcon('heroicon-m-map-pin')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ]),
                                    ]),

                                // Section 2: Unit & Diagnosa
                                FormsSection::make('Unit & Keluhan')
                                    ->icon('hugeicons-clipboard')
                                    ->schema([
                                        FormsGrid::make(2)
                                            ->schema([
                                                TextInput::make('nama_perangkat')
                                                    ->label('Nama Perangkat')
                                                    ->placeholder('Contoh: Laptop Lenovo Ideapad 3')
                                                    ->required(),

                                                TextInput::make('kelengkapan')
                                                    ->label('Kelengkapan')
                                                    ->placeholder('Unit, Charger, Dus...'),
                                            ]),

                                        Textarea::make('keluhan')
                                            ->label('Keluhan Pelanggan')
                                            ->rows(3)
                                            ->required()
                                            ->columnSpanFull(),

                                        Textarea::make('catatan_teknisi')
                                            ->label('Catatan Fisik (Optional)')
                                            ->placeholder('Cth: Lecet bezel, baut hilang satu')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // --- KOLOM KANAN (ADMINISTRASI) ---
                        FormsGroup::make()
                            ->schema([
                                FormsSection::make('Status & Penugasan')
                                    ->icon('hugeicons-settings-01')
                                    ->schema([
                                        TextInput::make('no_resi')
                                            ->label('No. Resi')
                                            ->default(fn () => 'SRV-'.now()->format('ymd').'-'.rand(100, 999))
                                            ->readOnly() // Readonly lebih baik visualnya daripada disabled untuk ID
                                            ->required(),

                                        Select::make('status')
                                            ->options([
                                                'pending' => 'Menunggu Antrian',
                                                'diagnosa' => 'Sedang Diagnosa',
                                                'waiting_part' => 'Menunggu Sparepart',
                                                'progress' => 'Sedang Dikerjakan',
                                                'done' => 'Selesai (Siap Ambil)',
                                                'cancel' => 'Dibatalkan',
                                            ])
                                            ->default('pending')
                                            ->native(false)
                                            ->required(),

                                        Select::make('technician_id')
                                            ->label('Teknisi')
                                            ->relationship('technician', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => auth()->user()->id),

                                        Select::make('jasa_id')
                                            ->label('Layanan Utama')
                                            ->relationship('jasa', 'nama_jasa')
                                            ->searchable()
                                            ->preload(),

                                        DatePicker::make('estimasi_selesai')
                                            ->label('Estimasi Selesai')
                                            ->native(false)
                                            ->default(now()->addDays(1)),
                                    ]),

                                FormsSection::make()
                                    ->schema([
                                        Placeholder::make('created_at')
                                            ->label('Waktu Penerimaan')
                                            ->content(fn ($record) => $record?->created_at?->format('d M Y, H:i') ?? now()->format('d M Y, H:i')),
                                    ]),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),

                FormsSection::make('Kelengkapan & Crosscheck')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->schema([
                        Forms\Components\Toggle::make('has_crosscheck')
                            ->label('Enable Input Crosscheck & Kelengkapan')
                            ->live()
                            ->default(false),

                        Forms\Components\Tabs::make('Attributes')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Crosscheck')
                                    ->icon('heroicon-m-clipboard-document-check')
                                    ->schema(static::getAttributeSchema(\App\Models\Crosscheck::class, 'crosschecks')),

                                Forms\Components\Tabs\Tab::make('Aplikasi')
                                    ->icon('heroicon-m-window')
                                    ->schema(static::getAttributeSchema(\App\Models\ListAplikasi::class, 'listAplikasis')),

                                Forms\Components\Tabs\Tab::make('Game')
                                    ->icon('heroicon-m-puzzle-piece')
                                    ->schema(static::getAttributeSchema(\App\Models\ListGame::class, 'listGames')),

                                Forms\Components\Tabs\Tab::make('OS')
                                    ->icon('heroicon-m-cpu-chip')
                                    ->schema(static::getAttributeSchema(\App\Models\ListOs::class, 'listOs')),
                            ])
                            ->visible(fn (\Filament\Forms\Get $get) => $get('has_crosscheck'))
                            ->columnSpanFull()
                            ->contained(true),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getAttributeSchema(string $modelClass, string $relationName): array
    {
        $parents = $modelClass::with('children')->whereNull('parent_id')->get();
        $schema = [];

        foreach ($parents as $parent) {
            $parentId = $parent->id;
            // Use a specific key format to identify these fields later
            $parentKey = "attr_{$relationName}_{$parentId}_parent";
            $childrenKey = "attr_{$relationName}_{$parentId}_children";

            $schema[] = FormsGroup::make([
                Forms\Components\Checkbox::make($parentKey)
                    ->label($parent->name)
                    ->live()
                    ->afterStateHydrated(function ($component, $record) use ($parentId, $relationName) {
                        if ($record && $record->{$relationName}->contains('id', $parentId)) {
                            $component->state(true);
                        }
                    }),

                Forms\Components\CheckboxList::make($childrenKey)
                    ->hiddenLabel()
                    ->options($parent->children->pluck('name', 'id'))
                    ->visible(fn (\Filament\Forms\Get $get) => $get($parentKey))
                    ->columns(3)
                    ->gridDirection('row')
                    ->afterStateHydrated(function ($component, $record) use ($parent, $relationName) {
                        if ($record) {
                            $childIds = $parent->children->pluck('id')->toArray();
                            $existingIds = $record->{$relationName}->pluck('id')->toArray();
                            $selected = array_intersect($childIds, $existingIds);
                            $component->state(array_values($selected));
                        }
                    }),
            ])->extraAttributes(['class' => 'mb-4 border-b pb-4 last:border-b-0']);
        }

        if (empty($schema)) {
            $schema[] = Forms\Components\Placeholder::make("no_{$relationName}")
                ->label('Tidak ada data tersedia')
                ->content('Silakan tambahkan data master terlebih dahulu.');
        }

        // Wrap in a scrolling container
        return [
            FormsGroup::make($schema)
                ->extraAttributes(['style' => 'max-height: 400px; overflow-y: auto; padding-right: 10px;']),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('no_resi')
                    ->label('No. Resi')
                    ->fontFamily(FontFamily::Mono)
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                TextColumn::make('created_at')
                    ->label('Waktu Penerimaan')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('member.nama_member')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama_perangkat')
                    ->label('Perangkat')
                    ->limit(25)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Antrian',
                        'diagnosa' => 'Diagnosa',
                        'waiting_part' => 'Wait Part',
                        'progress' => 'Proses',
                        'done' => 'Selesai',
                        'cancel' => 'Batal',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'diagnosa' => 'info',
                        'waiting_part' => 'warning',
                        'progress' => 'info',
                        'done' => 'success',
                        'cancel' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('estimasi_selesai')
                    ->label('Estimasi')
                    ->date('d M')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu Antrian',
                        'diagnosa' => 'Sedang Diagnosa',
                        'waiting_part' => 'Menunggu Sparepart',
                        'progress' => 'Sedang Dikerjakan',
                        'done' => 'Selesai',
                        'cancel' => 'Dibatalkan',
                    ])
                    ->default('pending')
                    ->indicateUsing(function (array $data): ?string {
                        $status = $data['value'] ?? null;
                        if (! $status) {
                            return null;
                        }

                        $labels = [
                            'pending' => 'Menunggu Antrian',
                            'diagnosa' => 'Sedang Diagnosa',
                            'waiting_part' => 'Menunggu Sparepart',
                            'progress' => 'Sedang Dikerjakan',
                            'done' => 'Selesai',
                            'cancel' => 'Dibatalkan',
                        ];

                        return 'Status: ' . ($labels[$status] ?? $status);
                    }),
                SelectFilter::make('technician_id')
                    ->label('Teknisi')
                    ->relationship('technician', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('print')
                        ->label('Cetak Invoice')
                        ->icon('heroicon-m-printer')
                        ->color('success')
                        ->url(fn (PenjadwalanService $record) => route('penjadwalan-service.print', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('print_crosscheck')
                        ->label('Cetak Checklist')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->color('info')
                        ->visible(fn (PenjadwalanService $record) => $record->has_crosscheck)
                        ->url(fn (PenjadwalanService $record) => route('penjadwalan-service.print-crosscheck', $record))
                        ->openUrlInNewTab(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->label('Menu')
                    ->tooltip('Menu Actions'),
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
                \Filament\Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        \Filament\Infolists\Components\Tabs\Tab::make('Detail')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                InfolistGrid::make(3)
                                    ->schema([
                                        // --- KOLOM KIRI ---
                                        InfolistGroup::make()
                                            ->schema([
                                                InfolistSection::make('Informasi Service')
                                                    ->icon('heroicon-m-device-phone-mobile')
                                                    ->schema([
                                                        // Header Besar
                                                        TextEntry::make('nama_perangkat')
                                                            ->label('Unit Service')
                                                            ->weight(FontWeight::Bold)
                                                            ->size(TextEntrySize::Large)
                                                            ->columnSpanFull(),

                                                        TextEntry::make('kelengkapan')
                                                            ->icon('heroicon-m-archive-box')
                                                            ->color('gray')
                                                            ->columnSpanFull(),

                                                        // Data Pemilik dalam Grid
                                                        InfolistGrid::make(2)
                                                            ->schema([
                                                                TextEntry::make('member.nama_member')
                                                                    ->label('Pemilik')
                                                                    ->icon('heroicon-m-user'),

                                                                TextEntry::make('member.no_hp')
                                                                    ->label('WhatsApp')
                                                                    ->icon('heroicon-m-phone')
                                                                    ->color('primary')
                                                                    ->url(fn ($record) => 'https://wa.me/'.$record->member->no_hp, true),
                                                            ])
                                                            ->extraAttributes(['class' => 'mt-4 border-t pt-4']), // Garis pemisah tipis
                                                    ]),

                                                InfolistSection::make('Diagnosa & Keluhan')
                                                    ->icon('heroicon-m-clipboard-document-list')
                                                    ->schema([
                                                        TextEntry::make('keluhan')
                                                            ->label('Keluhan Awal')
                                                            ->markdown(),

                                                        TextEntry::make('catatan_teknisi')
                                                            ->label('Catatan Teknisi')
                                                            ->placeholder('Belum ada catatan')
                                                            ->markdown()
                                                            ->color('gray')
                                                            ->extraAttributes(['class' => 'italic']),
                                                    ]),
                                            ])
                                            ->columnSpan(['lg' => 2]),

                                        // --- KOLOM KANAN ---
                                        InfolistGroup::make()
                                            ->schema([
                                                InfolistSection::make('Status')
                                                    ->compact()
                                                    ->schema([
                                                        TextEntry::make('no_resi')
                                                            ->fontFamily(FontFamily::Mono)
                                                            ->weight(FontWeight::Bold)
                                                            ->copyable()
                                                            ->icon('heroicon-m-qr-code'),

                                                        TextEntry::make('status')
                                                            ->badge()
                                                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                                                'pending' => 'Antrian',
                                                                'diagnosa' => 'Diagnosa',
                                                                'waiting_part' => 'Wait Part',
                                                                'progress' => 'Proses',
                                                                'done' => 'Selesai',
                                                                'cancel' => 'Batal',
                                                                default => $state,
                                                            })
                                                            ->color(fn (string $state): string => match ($state) {
                                                                'pending' => 'gray',
                                                                'diagnosa' => 'info',
                                                                'waiting_part' => 'warning',
                                                                'progress' => 'info',
                                                                'done' => 'success',
                                                                'cancel' => 'danger',
                                                                default => 'gray',
                                                            }),

                                                        TextEntry::make('created_at')
                                                            ->label('Masuk')
                                                            ->date('d M Y, H:i')
                                                            ->color('gray'),
                                                    ]),

                                                InfolistSection::make('Pengerjaan')
                                                    ->compact()
                                                    ->schema([
                                                        TextEntry::make('technician.name')
                                                            ->label('Teknisi')
                                                            ->icon('heroicon-m-user-circle')
                                                            ->placeholder('-'),

                                                        TextEntry::make('jasa.nama_jasa')
                                                            ->label('Layanan')
                                                            ->color('primary')
                                                            ->url(fn ($record) => $record->jasa ? JasaResource::getUrl('view', ['record' => $record->jasa]) : null),

                                                        TextEntry::make('estimasi_selesai')
                                                            ->label('Deadline')
                                                            ->date('d M Y')
                                                            ->icon('heroicon-m-calendar'),
                                                    ]),
                                            ])
                                            ->columnSpan(['lg' => 1]),
                                    ]),
                            ]),

                        \Filament\Infolists\Components\Tabs\Tab::make('Crosscheck')
                            ->visible(fn ($record) => $record->has_crosscheck)
                            ->icon('heroicon-m-clipboard-document-check')
                            ->schema([
                                InfolistGrid::make(2)
                                    ->schema([
                                        InfolistSection::make('Crosscheck & Kelengkapan')
                                            ->schema([
                                                TextEntry::make('crosschecks.name')
                                                    ->label('')
                                                    ->badge()
                                                    ->color('success')
                                                    ->listWithLineBreaks(),
                                            ]),

                                        InfolistSection::make('Aplikasi')
                                            ->schema([
                                                TextEntry::make('listAplikasis.name')
                                                    ->label('')
                                                    ->badge()
                                                    ->color('info')
                                                    ->listWithLineBreaks(),
                                            ]),

                                        InfolistSection::make('Game')
                                            ->schema([
                                                TextEntry::make('listGames.name')
                                                    ->label('')
                                                    ->badge()
                                                    ->color('warning')
                                                    ->listWithLineBreaks(),
                                            ]),

                                        InfolistSection::make('Sistem Operasi')
                                            ->schema([
                                                TextEntry::make('listOs.name')
                                                    ->label('')
                                                    ->badge()
                                                    ->color('danger')
                                                    ->listWithLineBreaks(),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    // ... relations dan pages tetap sama
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenjadwalanServices::route('/'),
            'create' => Pages\CreatePenjadwalanService::route('/create'),
            'view' => Pages\ViewPenjadwalanService::route('/{record}'),
            'edit' => Pages\EditPenjadwalanService::route('/{record}/edit'),
        ];
    }

}
