<?php

namespace App\Filament\Resources;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use App\Filament\Resources\Absensi\LiburCutiResource;
use App\Filament\Resources\LaporanPengajuanCutiResource\Pages;
use App\Models\LaporanPengajuanCuti;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class LaporanPengajuanCutiResource extends Resource
{
    protected static ?string $model = LaporanPengajuanCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $modelLabel = 'Pengajuan Cuti';

    protected static ?string $pluralModelLabel = 'Pengajuan Cuti';

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('liburCuti.user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('liburCuti.keperluan')
                    ->label('Keperluan')
                    ->badge()
                    ->formatStateUsing(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                        ? $state->getLabel()
                        : (filled($state) ? Keperluan::from($state)->getLabel() : null))
                    ->color(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                        ? $state->getColor()
                        : (filled($state) ? Keperluan::from($state)->getColor() : null))
                    ->sortable(),
                TextColumn::make('liburCuti.mulai_tanggal')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('liburCuti.sampai_tanggal')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('status_pengajuan')
                    ->label('Status Akhir')
                    ->badge()
                    ->formatStateUsing(fn(?StatusPengajuan $state) => $state?->getLabel())
                    ->color(fn(?StatusPengajuan $state) => $state?->getColor()),
            ])
            ->filters([
                SelectFilter::make('status_pengajuan')
                    ->label('Status Akhir')
                    ->options(
                        collect(StatusPengajuan::cases())
                            ->mapWithKeys(fn(StatusPengajuan $case) => [$case->value => $case->getLabel()])
                            ->all()
                    ),
                SelectFilter::make('user_id')
                    ->label('Nama')
                    ->options(
                        collect(LaporanPengajuanCuti::query()->get())
                            ->mapWithKeys(fn(LaporanPengajuanCuti $case) => [$case->liburCuti->user->id => $case->liburCuti->user->name])
                            ->all()
                    ),
            ])
            ->actions([
                Action::make('approve')
                    ->button()
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn(LaporanPengajuanCuti $record) => $record->status_pengajuan === StatusPengajuan::Pending)
                    ->action(function (LaporanPengajuanCuti $record): void {
                        $record->approveSubmission();
                    }),
                Action::make('reject')
                    ->button()
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn(LaporanPengajuanCuti $record) => $record->status_pengajuan === StatusPengajuan::Pending)
                    ->action(function (LaporanPengajuanCuti $record): void {
                        $record->rejectSubmission();
                    }),
                Action::make('lihat_libur')
                    ->button()
                    ->label('Lihat Data Cuti')
                    ->color('gray')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(LaporanPengajuanCuti $record) => LiburCutiResource::getUrl('index', [
                        'tableSearch' => $record->liburCuti?->user?->name,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Informasi Pengajuan')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('liburCuti.user.name')->label('Karyawan'),
                        TextEntry::make('liburCuti.keperluan')
                            ->label('Keperluan')
                            ->formatStateUsing(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                                ? $state->getLabel()
                                : (filled($state) ? Keperluan::from($state)->getLabel() : '-'))
                            ->badge()
                            ->color(fn(Keperluan|string|null $state) => $state instanceof Keperluan
                                ? $state->getColor()
                                : (filled($state) ? Keperluan::from($state)->getColor() : null)),
                        TextEntry::make('liburCuti.mulai_tanggal')
                            ->label('Mulai')
                            ->date('d F Y'),
                        TextEntry::make('liburCuti.sampai_tanggal')
                            ->label('Sampai')
                            ->date('d F Y')
                            ->placeholder('-'),
                    ]),
                    TextEntry::make('liburCuti.keterangan')
                        ->label('Keterangan')
                        ->columnSpanFull()
                        ->placeholder('-'),
                ]),
            Section::make('Status')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('status_pengajuan')
                            ->label('Status Akhir')
                            ->badge()
                            ->formatStateUsing(fn(?StatusPengajuan $state) => $state?->getLabel())
                            ->color(fn(?StatusPengajuan $state) => $state?->getColor()),
                    ]),
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
            'index' => Pages\ListLaporanPengajuanCutis::route('/'),
            'view' => Pages\ViewLaporanPengajuanCuti::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
