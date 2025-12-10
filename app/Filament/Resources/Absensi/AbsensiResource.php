<?php

namespace App\Filament\Resources\Absensi;

use App\Filament\Resources\Absensi\AbsensiResource\Pages;
use App\Models\Absensi;
use App\Models\Karyawan;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'hugeicons-clock-01';
    protected static ?string $navigationGroup = 'Absensi';
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
                            ToggleButtons::make('status')
                                ->label('Status')
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
                                ->required()
                                ->live(), // PENTING: Agar step berikutnya bereaksi realtime
                        ]),

                    // --- WIZARD STEP 2: CAMERA (Hanya jika Hadir) ---
                    Wizard\Step::make('Bukti Foto')
                        ->icon('heroicon-o-camera')
                        ->description('Ambil foto selfie di lokasi')
                        ->visible(fn (Get $get) => $get('status') === 'hadir') // Logic Skenario A & B
                        ->schema([
                            TakePicture::make('camera_test')
                                ->label('Ambil Foto')
                                ->disk('public')
                                ->directory('uploads/absensi')
                                ->visibility('public')
                                ->useModal(true)
                                ->showCameraSelector(true)
                                ->aspect('4:3')
                                ->imageQuality(80)
                                ->shouldDeleteOnEdit(false)
                                ->required(fn (Get $get) => $get('status') === 'hadir') // Wajib jika Hadir
                                ->columnSpanFull(),
                        ]),

                    // --- WIZARD STEP 3: KONFIRMASI & KETERANGAN ---
                    Wizard\Step::make('Konfirmasi')
                        ->icon('heroicon-o-paper-airplane')
                        ->description('Tambahkan keterangan & kirim')
                        ->schema([
                            Textarea::make('keterangan')
                                ->label('Keterangan Tambahan')
                                ->placeholder('Contoh: Keperluan mendadak / Meeting luar kantor')
                                ->rows(3)
                                ->columnSpanFull(),

                            // --- HIDDEN FIELDS (System Data) ---
                            // Kita sembunyikan (Hidden) agar UI bersih, tapi data tetap terkirim
                            Hidden::make('user_id')->default(auth()->id()),
                            Hidden::make('tanggal')->default(now()),
                            Hidden::make('jam_masuk')->default(now()->format('H:i')),
                            
                            // Field ini tetap visible tapi readonly agar user tau lokasi terdeteksi
                            // Atau bisa dibuat Hidden jika ingin benar-benar minimalist
                            TextInput::make('lat_absen')
                                ->label('Latitude')
                                ->readOnly()
                                ->extraInputAttributes(['id' => 'lat_absen'])
                                ->helperText('Koordinat lokasi terdeteksi.'),

                            TextInput::make('long_absen')
                                ->label('Longitude')
                                ->readOnly()
                                ->extraInputAttributes(['id' => 'long_absen']),
                        ]),
                ])
                ->columnSpanFull() // Agar wizard lebar penuh
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary">Simpan Absensi</button>'))
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Detail Kehadiran')
                    ->schema([
                        Grid::make(2)->schema([
                            // Tampilkan foto hanya jika ada (Hadir)
                            ImageEntry::make('camera_test')
                                ->label('Foto Absensi')
                                ->disk('public')
                                ->visibility('public')
                                ->height(200)
                                ->columnSpanFull()
                                ->visible(fn ($record) => $record->camera_test !== null), 

                            TextEntry::make('user.name')->label('Karyawan'),
                            TextEntry::make('tanggal')->date('d M Y'),
                            TextEntry::make('jam_masuk')->time('H:i'),
                            TextEntry::make('jam_keluar')->time('H:i')->placeholder('-'),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'hadir' => 'success',
                                    'izin' => 'warning',
                                    'sakit' => 'danger',
                                    default => 'gray',
                                }),
                        ]),
                        TextEntry::make('keterangan')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan') 
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_masuk')->time('H:i'),
                TextColumn::make('jam_keluar')
                    ->label('Jam Pulang')
                    ->formatStateUsing(function ($state, Absensi $record) {
                        if (in_array($record->status, ['izin', 'sakit', 'alpha', 'alpa'], true)) {
                            return '-';
                        }

                        return $state
                            ? Carbon::parse($state)->format('H:i')
                            : 'Belum Pulang';
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success',
                        'izin' => 'warning',
                        'sakit' => 'danger',
                        'alpha', 'alpa' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari_tanggal'),
                        DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari_tanggal'], fn ($q) => $q->whereDate('tanggal', '>=', $data['dari_tanggal']))
                            ->when($data['sampai_tanggal'], fn ($q) => $q->whereDate('tanggal', '<=', $data['sampai_tanggal']));
                    }),
                SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Action::make('pulang')
                //     ->label('Pulang')
                //     ->icon('heroicon-o-arrow-right-end-on-rectangle')
                //     ->color('success')
                //     ->requiresConfirmation()
                //     ->visible(fn (Absensi $record) => blank($record->jam_keluar))
                //     ->action(function (Absensi $record): void {
                //         $record->update([
                //             'jam_keluar' => now()->format('H:i:s'),
                //         ]);
                //     }),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'view' => Pages\ViewAbsensi::route('/{record}'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}
