<?php

namespace App\Filament\Resources\Absensi;

use App\Enums\StatusPengajuan;
use App\Filament\Resources\Absensi\LemburResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Models\Lembur;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LemburResource extends BaseResource
{
    protected static ?string $model = Lembur::class;

    protected static ?string $navigationIcon = 'hugeicons-clock-05';

    protected static ?string $navigationGroup = 'Personalia';

    protected static ?string $navigationLabel = 'Lembur';

    protected static ?int $navigationSort = 2;

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
            ->columns(3)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        Section::make('Detail Pengajuan')
                            ->description('Informasi karyawan dan alasan lembur.')
                            ->icon('heroicon-m-clipboard-document-list')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('Nama Karyawan')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(auth()->user()->id)
                                    ->required(),

                                Forms\Components\DatePicker::make('tanggal')
                                    ->label('Tanggal Lembur')
                                    ->native(false)
                                    ->displayFormat('d F Y')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\Textarea::make('keperluan')
                                    ->label('Keperluan')
                                    ->placeholder('Jelaskan detail pekerjaan...')
                                    ->required()
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\FileUpload::make('bukti')
                                    ->label('Bukti Foto')
                                    ->disk('public')
                                    ->directory('uploads/lembur')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'])
                                    ->saveUploadedFileUsing(function ($file, $component) {
                                        return \App\Support\WebpUpload::store($component, $file);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Pelaksanaan & Status')
                            ->description('Waktu lembur dan validasi.')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Forms\Components\Group::make()->schema([
                                    Forms\Components\TimePicker::make('jam_mulai')
                                        ->label('Mulai')
                                        ->seconds(false)
                                        ->default(now())
                                        ->required(),

                                    Forms\Components\TimePicker::make('jam_selesai')
                                        ->label('Selesai')
                                        ->seconds(false)
                                        ->nullable(),
                                ])->columns(2),

                                Forms\Components\Select::make('status')
                                    ->options(StatusPengajuan::class)
                                    ->default(StatusPengajuan::Pending)
                                    ->native(false)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),

                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Supervisor')
                                    ->rows(2)
                                    ->visible(fn (Get $get) => in_array($get('status'), [
                                        StatusPengajuan::Diterima->value,
                                        StatusPengajuan::Ditolak->value,
                                    ])),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistGrid::make(3)->schema([
                    InfolistGroup::make([
                        InfolistSection::make('Informasi Pengajuan')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                InfolistGrid::make(2)->schema([
                                    TextEntry::make('user.name')
                                        ->label('Karyawan')
                                        ->weight('bold')
                                        ->icon('heroicon-m-user'),

                                    TextEntry::make('tanggal')
                                        ->label('Tanggal')
                                        ->date('d F Y')
                                        ->icon('heroicon-m-calendar'),
                                ]),

                                TextEntry::make('keperluan')
                                    ->markdown()
                                    ->prose(),

                                \Filament\Infolists\Components\ImageEntry::make('bukti')
                                    ->hiddenLabel()
                                    ->width('100%')
                                    ->height('auto')
                                    ->extraImgAttributes(['class' => 'rounded-xl shadow-sm border border-gray-100']),
                            ]),
                    ])->columnSpan(2),

                    InfolistGroup::make([
                        InfolistSection::make('Validasi & Status')
                            ->icon('heroicon-m-check-badge')
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->label('Status Saat Ini'),

                                InfolistGroup::make([
                                    TextEntry::make('jam_mulai')
                                        ->time('H:i')
                                        ->label('Mulai')
                                        ->icon('heroicon-m-clock'),
                                    TextEntry::make('jam_selesai')
                                        ->time('H:i')
                                        ->placeholder('-')
                                        ->label('Selesai')
                                        ->icon('heroicon-m-check-circle'),
                                ])->columns(2),

                                TextEntry::make('catatan')
                                    ->label('Catatan')
                                    ->placeholder('-')
                                    ->color('gray')
                                    ->visible(fn ($record) => filled($record->catatan)),

                                TextEntry::make('created_at')
                                    ->dateTime('d M Y, H:i')
                                    ->color('gray')
                                    ->size(TextEntry\TextEntrySize::Small),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('bukti')
                    ->label('Bukti')
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder.png')), // Optional

                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->weight('bold')
                    ->description(fn (Lembur $record) => $record->user->email ?? '-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_mulai')
                    ->label('Waktu')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(fn (Lembur $record) => Carbon::parse($record->jam_mulai)->format('H:i').' - '.($record->jam_selesai ? Carbon::parse($record->jam_selesai)->format('H:i') : '?'))
                    ->color('gray')
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getLabel()
                        : (filled($state) ? StatusPengajuan::from($state)->getLabel() : null))
                    ->color(fn (StatusPengajuan|string|null $state) => $state instanceof StatusPengajuan
                        ? $state->getColor()
                        : (filled($state) ? StatusPengajuan::from($state)->getColor() : null))
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
