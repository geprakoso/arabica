<?php

namespace App\Filament\Resources\Absensi;

use Filament\Forms;
use Filament\Tables;
use App\Models\Absensi;
use Filament\Forms\Get;
use App\Models\Karyawan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Forms\Components\Actions\Action as FormAction;
use App\Filament\Resources\Absensi\AbsensiResource\Pages;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Forms\Components\Section;
use emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'hugeicons-clock-01';
    protected static ?string $navigationGroup = 'Absensi';
    protected static ?string $pluralLabel = 'Absensi';
    protected static ?string $navigationLabel = 'Absen';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user?->can('view_any_absensi::absensi')
            || $user?->can('view_limit_absensi::absensi')
            || false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        // When only view_limit is granted, restrict to own records.
        if ($user?->can('view_limit_absensi::absensi') && ! $user->can('view_any_absensi::absensi')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // --- WIZARD STEP 1: PILIH STATUS ---
                    Wizard\Step::make('Status Kehadiran')
                        ->icon('heroicon-o-check-circle')
                        ->description('Pilih status absensi Anda hari ini')
                        ->schema([
                            Section::make()
                                ->schema([
                                    ToggleButtons::make('status')
                                        ->label('Jenis Absensi')
                                        ->options([
                                            'hadir' => 'Hadir',
                                            'izin'  => 'Izin',
                                            'sakit' => 'Sakit',
                                            // Alpha tidak ditampilkan sesuai request
                                        ])
                                        ->icons([
                                            'hadir' => 'heroicon-o-map-pin',
                                            'izin'  => 'heroicon-o-document-text',
                                            'sakit' => 'heroicon-o-face-frown',
                                        ])
                                        ->colors([
                                            'hadir' => 'success',
                                            'izin'  => 'warning',
                                            'sakit' => 'danger',
                                        ])
                                        ->default('hadir')
                                        ->inline()
                                        ->grouped() // Make it look like a segmented control
                                        ->required()
                                        ->live(), // PENTING: Agar step berikutnya bereaksi realtime
                                ])
                                ->compact(), // Minimalist feel
                        ]),

                    // --- WIZARD STEP 2: CAMERA (Hanya jika Hadir) ---
                    Wizard\Step::make('Bukti Foto')
                        ->icon('heroicon-o-camera')
                        ->description('Ambil foto selfie di lokasi')
                        ->visible(fn(Get $get) => $get('status') === 'hadir') // Logic Skenario A & B
                        ->schema([
                            Section::make('Foto Selfie')
                                ->description('Pastikan wajah terlihat jelas dan berada di lokasi.')
                                ->icon('heroicon-o-camera')
                                ->schema([
                                    TakePicture::make('camera_test')
                                        ->hiddenLabel() // Label moved to section
                                        ->disk('public')
                                        ->directory('uploads/absensi')
                                        ->visibility('public')
                                        ->useModal(true)
                                        ->showCameraSelector(true)
                                        ->aspect('4:3')
                                        ->imageQuality(80)
                                        ->shouldDeleteOnEdit(false)
                                        ->required(fn(Get $get) => $get('status') === 'hadir') // Wajib jika Hadir
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // --- WIZARD STEP 3: KONFIRMASI & KETERANGAN ---
                    Wizard\Step::make('Konfirmasi')
                        ->icon('heroicon-o-paper-airplane')
                        ->description('Tinjau & Kirim')
                        ->schema([
                            Section::make('Keterangan Tambahan')
                                ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                ->schema([
                                    Textarea::make('keterangan')
                                        ->hiddenLabel()
                                        ->placeholder('Contoh: Meeting luar kantor, keperluan mendadak, dll.')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Deteksi Lokasi')
                                ->icon('heroicon-o-map')
                                ->description('Koordinat lokasi Anda saat ini.')
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        TextInput::make('lat_absen')
                                            ->label('Latitude')
                                            ->prefixIcon('heroicon-m-map-pin')
                                            ->readOnly()
                                            ->extraInputAttributes(['id' => 'lat_absen']), // Keep ID for JS geolocation script
                                        TextInput::make('long_absen')
                                            ->label('Longitude')
                                            ->prefixIcon('heroicon-m-map-pin')
                                            ->readOnly()
                                            ->extraInputAttributes(['id' => 'long_absen']), // Keep ID for JS geolocation script
                                    ]),
                                ])
                                ->collapsible()
                                ->collapsed(), // Auto collapse to keep UI clean

                            // System Fields
                            Hidden::make('user_id')->default(Auth::id()),
                            Hidden::make('tanggal')->default(fn() => now()->toDateString()),
                            Hidden::make('jam_masuk')->default(now()->format('H:i')),
                        ]),
                ])
                    ->columnSpanFull() // Agar wizard lebar penuh
                    ->submitAction(
                        Action::make('submit')
                            ->label('Simpan Absensi')
                            ->color('success')
                            ->icon('heroicon-m-check')

                    )
                    ->cancelAction(
                        Action::make('cancel')
                            ->label('Batal')
                            ->color('danger')
                            ->icon('heroicon-m-x-mark')
                    )
                    ->previousAction(
                        fn(FormAction $action) =>
                        $action
                            ->label('Sebelumnya')
                            ->color('secondary')
                            ->icon('heroicon-m-arrow-left')
                    ),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make()
                    ->schema([
                        Split::make([
                            // Sisi Kiri: Foto
                            Group::make([
                                ImageEntry::make('camera_test')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->width('100%')
                                    ->height('auto')
                                    ->extraImgAttributes([
                                        'class' => 'rounded-lg shadow-md object-cover w-full aspect-[4/3]',
                                        'alt' => 'Foto Absensi',
                                    ])
                                    ->columnSpanFull()
                                    ->defaultImageUrl(url('/images/placeholder-image.jpg')) // Optional placeholder
                                    ->visible(fn($record) => $record->camera_test !== null),

                                TextEntry::make('status')
                                    ->label('Status Kehadiran')
                                    ->badge()
                                    ->size('lg')
                                    ->color(fn(string $state): string => match ($state) {
                                        'hadir' => 'success',
                                        'izin' => 'warning',
                                        'sakit' => 'danger',
                                        default => 'gray',
                                    })
                                    ->icon(fn(string $state): string => match ($state) {
                                        'hadir' => 'heroicon-m-check-circle',
                                        'izin' => 'heroicon-m-document-text',
                                        'sakit' => 'heroicon-m-plus-circle',
                                        default => 'heroicon-m-question-mark-circle',
                                    })
                                    ->alignCenter(),
                            ])->grow(false),

                            // Sisi Kanan: Informasi Detail
                            Group::make([
                                InfolistSection::make('Informasi Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Nama')
                                            ->weight('bold')
                                            ->icon('heroicon-m-user-circle'),
                                        TextEntry::make('user.email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->color('gray'),
                                    ])
                                    ->columns(2),

                                InfolistSection::make('Waktu & Lokasi')
                                    ->icon('heroicon-m-clock')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('tanggal')
                                            ->date('l, d M Y')
                                            ->icon('heroicon-m-calendar'),
                                        TextEntry::make('jam_masuk')
                                            ->time('H:i')
                                            ->icon('heroicon-m-arrow-right-end-on-rectangle')
                                            ->color('success'),
                                        TextEntry::make('jam_keluar')
                                            ->time('H:i')
                                            ->placeholder('Belum Pulang')
                                            ->icon('heroicon-m-arrow-left-start-on-rectangle')
                                            ->color('danger'),
                                        TextEntry::make('lat_absen')
                                            ->label('Latitude')
                                            ->icon('heroicon-m-map-pin')
                                            ->visible(fn($record) => $record->lat_absen),
                                        TextEntry::make('long_absen')
                                            ->label('Longitude')
                                            ->icon('heroicon-m-map-pin')
                                            ->visible(fn($record) => $record->long_absen),
                                    ])
                                    ->columns(2),

                                InfolistSection::make('Keterangan')
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->compact()
                                    ->schema([
                                        TextEntry::make('keterangan')
                                            ->hiddenLabel()
                                            ->markdown()
                                            ->placeholder('Tidak ada keterangan tambahan.'),
                                    ]),
                            ])->grow(true),
                        ])->from('md'), // Split only on medium screens and up
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->weight('bold')
                    ->description(fn(Absensi $record) => $record->user->email ?? '-')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn(Absensi $record) => $record->tanggal->translatedFormat('l')) // Hari
                    ->icon('heroicon-m-calendar')
                    ->sortable(),

                TextColumn::make('jam_masuk')
                    ->label('Masuk')
                    ->time('H:i')
                    ->icon('heroicon-m-arrow-right-end-on-rectangle')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('jam_keluar')
                    ->label('Pulang')
                    ->formatStateUsing(function ($state, Absensi $record) {
                        // Jika status bukan hadir, tampilkan strip
                        if (in_array($record->status, ['izin', 'sakit', 'alpha', 'alpa'], true)) {
                            return '-';
                        }
                        return $state ? Carbon::parse($state)->format('H:i') : 'Belum Pulang';
                    })
                    ->icon(fn($state) => $state ? 'heroicon-m-arrow-left-start-on-rectangle' : null)
                    ->color(fn($state) => $state ? 'danger' : 'gray')
                    ->description(fn($state, Absensi $record) => $record->status === 'hadir' && !$state ? 'Sedang bekerja' : null),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'warning',
                        'sakit' => 'danger',
                        'alpha', 'alpa' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'hadir' => 'heroicon-m-check-circle',
                        'izin' => 'heroicon-m-document-text',
                        'sakit' => 'heroicon-m-plus-circle',
                        'alpha', 'alpa' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari_tanggal')->native(false),
                        DatePicker::make('sampai_tanggal')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari_tanggal'], fn($q) => $q->whereDate('tanggal', '>=', $data['dari_tanggal']))
                            ->when($data['sampai_tanggal'], fn($q) => $q->whereDate('tanggal', '<=', $data['sampai_tanggal']));
                    }),
                SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                    ])
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([

                    Action::make('detail')
                        ->label('Detail Lengkap')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading('Detail Absensi')
                        ->modalWidth('5xl')
                        ->modalSubmitAction(false)
                        ->slideOver()
                        ->infolist(fn(Infolist $infolist) => static::infolist($infolist)),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->tooltip('Menu Aksi'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),

            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}
