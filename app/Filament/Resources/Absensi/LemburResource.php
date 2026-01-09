<?php

namespace App\Filament\Resources\Absensi;

use App\Enums\StatusPengajuan;
use App\Filament\Resources\Absensi\LemburResource\Pages;
use App\Filament\Resources\Absensi\LemburResource\RelationManagers;
use App\Models\Lembur;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Grid as InfolistGrid;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Date;

class LemburResource extends BaseResource
{
    protected static ?string $model = Lembur::class;

    protected static ?string $navigationIcon = 'hugeicons-clock-05';
    protected static ?string $navigationGroup = 'Kepegawaian';
    protected static ?string $navigationLabel = 'Lembur';

    public static function canViewAny(): bool
    {
        $user = \Filament\Facades\Filament::auth()->user();

        return $user?->can('view_any_lembur')
            || $user?->can('view_any_absensi::lembur') // format lama
            || $user?->can('view_limit_lembur')
            || $user?->can('view_limit_absensi::lembur') // format lama
            || false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = \Filament\Facades\Filament::auth()->user();

        // Jika hanya punya izin view_limit, batasi ke data milik sendiri.
        if (
            (
                $user?->can('view_limit_lembur') ||
                $user?->can('view_limit_absensi::lembur')
            ) && ! (
                $user?->can('view_any_lembur') ||
                $user?->can('view_any_absensi::lembur')
            )
        ) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3) // Grid utama 3 kolom
            ->schema([

                // === KOLOM KIRI (DATA LEMBUR) ===
                Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Informasi Dasar
                        Section::make('Informasi Pengajuan')
                            ->description('Detail karyawan dan alasan lembur.')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama Karyawan')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('keperluan') // Ganti Textarea agar muat deskripsi panjang
                                    ->label('Keperluan Lembur')
                                    ->placeholder('Jelaskan detail pekerjaan yang dilakukan...')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        // Section 2: Waktu
                        Section::make('Waktu Pelaksanaan')
                            ->icon('heroicon-m-clock')
                            ->columns(2) // Grid 2 kolom
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal Lembur')
                                    ->native(false) // Tampilan kalender modern
                                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : '-')
                                    ->default(now())
                                    ->required()
                                    ->columnSpanFull(), // Tanggal full width di atas jam

                                Forms\Components\TimePicker::make('jam_mulai')
                                    ->label('Jam Mulai')
                                    ->seconds(false)
                                    ->native(false)
                                    ->default(now())
                                    ->required(),

                                Forms\Components\TimePicker::make('jam_selesai')
                                    ->label('Jam Selesai')
                                    ->seconds(false)
                                    ->native(false)
                                    ->nullable(),
                            ]),
                    ]),

                // === KOLOM KANAN (VALIDASI) ===
                Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Status & Approval
                        Section::make('Validasi')
                            ->description('Status persetujuan pengajuan.')
                            ->icon('heroicon-m-check-badge')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status Pengajuan')
                                    ->options(StatusPengajuan::class)
                                    ->default(StatusPengajuan::Pending)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live() // Agar field catatan di bawahnya reaktif
                                    ->required(),

                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Supervisor')
                                    ->placeholder('Alasan diterima/ditolak...')
                                    ->rows(4)
                                    ->visible(fn(Get $get) => in_array($get('status'), [
                                        StatusPengajuan::Diterima->value,
                                        StatusPengajuan::Ditolak->value,
                                        // Pastikan value ini sesuai dengan Enum kamu (misal: 'diterima', 'ditolak' atau 1, 2)
                                        // Jika enum kamu mengembalikan string langsung, hapus ->value
                                    ])),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(3) // Grid utama 3 kolom
            ->schema([

                // === KOLOM KIRI (DATA UTAMA) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        // Section 1: Informasi Karyawan & Keperluan
                        InfolistSection::make('Informasi Pengajuan')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                TextEntry::make('user.name') // Mengambil nama dari relasi user
                                    ->label('Nama Karyawan')
                                    ->icon('heroicon-m-user')
                                    ->weight('bold'),

                                TextEntry::make('keperluan')
                                    ->label('Keperluan Lembur')
                                    ->columnSpanFull()
                                    ->markdown(), // Agar teks panjang rapi
                            ]),

                        // Section 2: Waktu
                        InfolistSection::make('Waktu Pelaksanaan')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                InfolistGrid::make(3) // Membagi baris waktu jadi 3
                                    ->schema([
                                        TextEntry::make('tanggal')
                                            ->label('Tanggal')
                                            ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->locale('id')->translatedFormat('d F Y') : '-') // Format tanggal Indonesia friendly
                                            ->icon('heroicon-m-calendar'),

                                        TextEntry::make('jam_mulai')
                                            ->label('Mulai')
                                            ->time('H:i')
                                            ->icon('heroicon-m-play-circle')
                                            ->color('success'), // Hijau untuk mulai

                                        TextEntry::make('jam_selesai')
                                            ->label('Selesai')
                                            ->time('H:i')
                                            ->icon('heroicon-m-stop-circle')
                                            ->placeholder('Belum selesai')
                                            ->color('danger'), // Merah untuk selesai
                                    ]),
                            ]),
                    ]),

                // === KOLOM KANAN (STATUS) ===
                InfolistGroup::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        // Section 3: Validasi
                        InfolistSection::make('Status Validasi')
                            ->icon('heroicon-m-check-badge')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status Pengajuan')
                                    ->badge(), // Otomatis ambil warna dari Enum

                                TextEntry::make('catatan')
                                    ->label('Catatan Supervisor')
                                    ->placeholder('Tidak ada catatan.')
                                    ->prose(), // Format teks paragraf
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->description(fn(Lembur $record) => $record->user->email ?? '-')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->description(fn(Lembur $record) => $record->tanggal->locale('id')->translatedFormat('l')) // Hari
                    ->icon('heroicon-m-calendar')
                    ->sortable(),

                TextColumn::make('jam_mulai')
                    ->label('Waktu')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(fn(Lembur $record) => Carbon::parse($record->jam_mulai)->format('H:i') . ' - ' . ($record->jam_selesai ? Carbon::parse($record->jam_selesai)->format('H:i') : '?')),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getLabel()
                        : (filled($state) ? StatusPengajuan::from($state)->getLabel() : null))
                    ->color(fn(StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getColor()
                        : (filled($state) ? StatusPengajuan::from($state)->getColor() : null))
                    ->icon(fn(StatusPengajuan|string|null $state) => match ($state instanceof StatusPengajuan ? $state->value : $state) {
                        'diterima', 1, '1' => 'heroicon-m-check-circle',
                        'ditolak', 2, '2' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-clock',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLemburs::route('/'),
            'create' => Pages\CreateLembur::route('/create'),
            'edit' => Pages\EditLembur::route('/{record}/edit'),
            'view' => Pages\ViewLembur::route('/{record}'),
        ];
    }
}
