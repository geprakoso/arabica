<?php

namespace App\Filament\Resources;

use App\Enums\Keperluan;
use App\Enums\StatusPengajuan;
use App\Filament\Resources\Absensi\LiburCutiResource;
use App\Filament\Resources\LaporanPengajuanCutiResource\Pages;
use App\Models\LaporanPengajuanCuti;
use Filament\Actions\StaticAction;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Grid as InfolistGrid;
use Filament\Infolists\Components\Group as InfolistGroup;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class LaporanPengajuanCutiResource extends BaseResource
{
    protected static ?string $model = LaporanPengajuanCuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Laporan Pengajuan Cuti';

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
            ->recordAction('detail')
            ->columns([
                TextColumn::make('liburCuti.user.name')
                    ->label('Karyawan')
                    ->icon('heroicon-m-user-circle')
                    ->weight('bold')
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
                    ->icon('heroicon-m-calendar')
                    ->sortable(),
                TextColumn::make('liburCuti.sampai_tanggal')
                    ->label('Sampai')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('status_pengajuan')
                    ->label('Status Akhir')
                    ->badge()
                    ->formatStateUsing(fn(?StatusPengajuan $state) => $state?->getLabel())
                    ->color(fn(?StatusPengajuan $state) => $state?->getColor())
                    ->icon(fn(?StatusPengajuan $state) => match ($state) {
                        StatusPengajuan::Diterima => 'heroicon-m-check-circle',
                        StatusPengajuan::Ditolak => 'heroicon-m-x-circle',
                        StatusPengajuan::Pending => 'heroicon-m-clock',
                        default => null,
                    }),
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
                    ->searchable()
                    ->preload()
                    ->relationship('liburCuti.user', 'name'),
            ])
            ->actions([
                Action::make('detail')
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->extraAttributes(['class' => 'hidden']) // Sembunyikan tombol detail, hanya lewat row click
                    ->modalHeading('Detail Pengajuan Cuti')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
                    ->extraModalFooterActions(fn(LaporanPengajuanCuti $record) => [
                        Action::make('approve-from-detail')
                            ->label('Setujui')
                            ->color('success')
                            ->icon('heroicon-m-check')
                            ->visible(fn() => $record->status_pengajuan === StatusPengajuan::Pending)
                            ->action(function (LaporanPengajuanCuti $record): void {
                                $record->approveSubmission();
                            })
                            ->cancelParentActions(),
                        Action::make('reject-from-detail')
                            ->label('Tolak')
                            ->color('danger')
                            ->icon('heroicon-m-x-mark')
                            ->visible(fn() => $record->status_pengajuan === StatusPengajuan::Pending)
                            ->action(function (LaporanPengajuanCuti $record): void {
                                $record->rejectSubmission();
                            })
                            ->cancelParentActions(),
                    ])
                    ->infolist(fn(Infolist $infolist) => static::infolist($infolist)),
                Action::make('approve')
                    ->button()
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-m-check')
                    ->requiresConfirmation()
                    ->visible(fn(LaporanPengajuanCuti $record) => $record->status_pengajuan === StatusPengajuan::Pending)
                    ->action(function (LaporanPengajuanCuti $record): void {
                        $record->approveSubmission();
                    }),
                Action::make('reject')
                    ->button()
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->requiresConfirmation()
                    ->visible(fn(LaporanPengajuanCuti $record) => $record->status_pengajuan === StatusPengajuan::Pending)
                    ->action(function (LaporanPengajuanCuti $record): void {
                        $record->rejectSubmission();
                    }),
                Action::make('lihat_libur')
                    ->iconButton()
                    ->tooltip('Lihat Data Cuti')
                    ->color('gray')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(LaporanPengajuanCuti $record) => LiburCutiResource::getUrl('index', [
                        'tableSearch' => $record->liburCuti?->user?->name,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                infolistGrid::make(4)
                    ->schema([
                        InfolistGroup::make()
                            ->schema([
                                InfolistSection::make('Informasi Pengajuan')
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                TextEntry::make('liburCuti.user.name')
                                                    ->label('Karyawan'),
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
                                            ])
                                    ]),
                                infolistSection::make('keterangan')
                                    ->icon('heroicon-m-calendar')
                                    ->schema([
                                        TextEntry::make('liburCuti.keterangan')
                                            ->label('')
                                            ->placeholder('-'),
                                    ])
                            ])
                            ->columnSpan(3),
                        InfolistGroup::make()
                            ->schema([
                                infolistSection::make('Informasi Akhir')
                                    ->schema([
                                        TextEntry::make('status_pengajuan')
                                            ->label('Status Akhir')
                                            ->badge()
                                            ->formatStateUsing(fn(?StatusPengajuan $state) => $state?->getLabel())
                                            ->color(fn(?StatusPengajuan $state) => $state?->getColor()),
                                    ])
                            ])
                            ->columnSpan(1),
                    ])
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
