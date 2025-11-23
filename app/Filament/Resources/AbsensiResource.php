<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Filament\Resources\AbsensiResource\RelationManagers;
use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Karyawan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Section::make('Detail Kehadiran')
                ->schema([
                    //Camera Module
                    TakePicture::make('camera_test')
                        ->label('Camera Test')
                        ->disk('public')
                        ->directory('uploads/services/payment_receipts_proof')
                        ->visibility('public')
                        ->useModal(true)
                        ->showCameraSelector(true)
                        ->aspect('4:3')
                        ->imageQuality(80)
                        ->shouldDeleteOnEdit(false),

                    // Otomatis pakai user yang sedang login; karyawan tidak bisa diubah
                    Select::make('user_id')
                        ->label('Karyawan')
                        ->options(
                            Karyawan::query()
                                ->whereNotNull('user_id')
                                ->pluck('nama_karyawan', 'user_id')
                        )
                        ->default(fn () => auth()->id())
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    // Tanggal Absen
                    DatePicker::make('tanggal')
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->default(today()),

                    // Jam Masuk & Keluar
                    TimePicker::make('jam_masuk')
                        ->default(now())
                        ->seconds(false) // Biasanya absensi tidak butuh detik
                        ->disabled()
                        ->dehydrated()
                        ->required(),
                    
                    TimePicker::make('jam_keluar')
                        ->label('Jam Pulang')
                        ->seconds(false),

                    // Status dengan Pilihan
                    Select::make('status')
                        ->options([
                            'hadir' => 'Hadir',
                            'izin' => 'Izin',
                            'sakit' => 'Sakit',
                            'alpha' => 'Alpha',
                        ])
                        ->default('hadir')
                        ->required(),

                    Textarea::make('keterangan')
                        ->columnSpanFull(),
                ])->columns(2),

                TextInput::make('lat_absen')
                    ->required()
                    ->readOnly()
                    ->extraInputAttributes(['id' => 'lat_absen']), // ID untuk selector JS

                TextInput::make('long_absen')
                    ->required()
                    ->readOnly()
                    ->extraInputAttributes(['id' => 'long_absen'])
                    ->helperText('Pastikan izin lokasi browser diaktifkan.'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_masuk')
                    ->time('H:i'),

                TextColumn::make('jam_keluar')
                    ->time('H:i')
                    ->placeholder('Belum Pulang'), // Jika kosong tampilkan ini

                // Badge Status supaya warna-warni
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hadir' => 'success', // Hijau
                        'izin' => 'warning',  // Kuning
                        'sakit' => 'danger',  // Merah
                        'alpha' => 'gray',    // Abu-abu
                        'alpa' => 'gray',     // fallback jika data lama pakai ejaan lama
                        default => 'gray',    // fallback agar tidak error
                    }),
                    
                ])
            ->defaultSort('created_at', 'desc') // Data terbaru paling atas
            ->filters([
                //
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
            
                // Filter Select Status
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'izin' => 'Izin',
                        'sakit' => 'Sakit',
                        'alpha' => 'Alpha',
                        'alpa' => 'Alpa', // fallback data lama
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
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'view' => Pages\ViewAbsensi::route('/{record}'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}
