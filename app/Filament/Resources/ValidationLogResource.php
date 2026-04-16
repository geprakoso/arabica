<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationLogResource\Pages;
use App\Models\ValidationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ValidationLogResource extends Resource
{
    protected static ?string $model = ValidationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Log Validasi';

    protected static ?string $pluralLabel = 'Log Validasi';

    protected static ?string $label = 'Log Validasi';

    protected static ?string $slug = 'validation-logs';

    protected static ?int $navigationSort = 99;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_resolved', false)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('is_resolved', false)->count();

        return $count > 0 ? 'danger' : 'success';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Informasi Error')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->iconColor('danger')
                        ->description('Detail kesalahan validasi yang tercatat')
                        ->schema([
                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('source_type')
                                        ->label('Sumber')
                                        ->badge()
                                        ->color('primary')
                                        ->icon('heroicon-o-rectangle-stack'),

                                    Infolists\Components\TextEntry::make('source_action')
                                        ->label('Aksi')
                                        ->badge()
                                        ->color('gray')
                                        ->icon('heroicon-o-cursor-arrow-rays'),

                                    Infolists\Components\TextEntry::make('severity')
                                        ->label('Tingkat')
                                        ->badge()
                                        ->color(fn ($state) => match ($state) {
                                            'info' => 'info',
                                            'warning' => 'warning',
                                            'error', 'critical' => 'danger',
                                            default => 'gray',
                                        })
                                        ->icon(fn ($state) => match ($state) {
                                            'info' => 'heroicon-o-information-circle',
                                            'warning' => 'heroicon-o-exclamation-triangle',
                                            'error' => 'heroicon-o-x-circle',
                                            'critical' => 'heroicon-o-fire',
                                            default => 'heroicon-o-question-mark-circle',
                                        }),
                                ]),

                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('validation_type')
                                        ->label('Tipe Validasi')
                                        ->badge()
                                        ->color('warning')
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'duplicate' => 'Duplikat',
                                            'stock' => 'Stok',
                                            'required' => 'Wajib Diisi',
                                            'format' => 'Format',
                                            'business_rule' => 'Aturan Bisnis',
                                            'minimum_items' => 'Minimum Item',
                                            'batch_not_found' => 'Batch Tidak Ditemukan',
                                            default => $state,
                                        }),

                                    Infolists\Components\TextEntry::make('field_name')
                                        ->label('Nama Field')
                                        ->icon('heroicon-o-code-bracket')
                                        ->placeholder('—'),

                                    Infolists\Components\TextEntry::make('error_code')
                                        ->label('Kode Error')
                                        ->icon('heroicon-o-hashtag')
                                        ->badge()
                                        ->color('danger')
                                        ->placeholder('—'),
                                ]),

                            Infolists\Components\TextEntry::make('error_message')
                                ->label('Pesan Error')
                                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                ->columnSpanFull()
                                ->prose()
                                ->markdown(),
                        ]),

                    Infolists\Components\Section::make('Data Input')
                        ->icon('heroicon-o-document-text')
                        ->iconColor('info')
                        ->description('Data yang dikirim saat validasi gagal')
                        ->collapsed()
                        ->schema([
                            Infolists\Components\KeyValueEntry::make('input_data')
                                ->label('')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),

                Infolists\Components\Group::make([
                    Infolists\Components\Section::make('Status Penyelesaian')
                        ->icon('heroicon-o-check-badge')
                        ->iconColor(fn ($record) => $record->is_resolved ? 'success' : 'warning')
                        ->schema([
                            Infolists\Components\IconEntry::make('is_resolved')
                                ->label('Status')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-clock')
                                ->trueColor('success')
                                ->falseColor('warning'),

                            Infolists\Components\TextEntry::make('resolved_at')
                                ->label('Diselesaikan Pada')
                                ->dateTime('d M Y, H:i')
                                ->icon('heroicon-o-calendar')
                                ->placeholder('Belum diselesaikan')
                                ->visible(fn ($record) => $record->is_resolved),

                            Infolists\Components\TextEntry::make('resolved_by')
                                ->label('Diselesaikan Oleh')
                                ->icon('heroicon-o-user')
                                ->placeholder('—')
                                ->visible(fn ($record) => $record->is_resolved),

                            Infolists\Components\TextEntry::make('resolution_notes')
                                ->label('Catatan Penyelesaian')
                                ->icon('heroicon-o-pencil-square')
                                ->placeholder('Tidak ada catatan')
                                ->visible(fn ($record) => $record->is_resolved),
                        ]),

                    Infolists\Components\Section::make('Konteks Request')
                        ->icon('heroicon-o-globe-alt')
                        ->iconColor('gray')
                        ->collapsed()
                        ->schema([
                            Infolists\Components\TextEntry::make('user_name')
                                ->label('Pengguna')
                                ->icon('heroicon-o-user-circle'),

                            Infolists\Components\TextEntry::make('ip_address')
                                ->label('Alamat IP')
                                ->icon('heroicon-o-signal')
                                ->badge()
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('method')
                                ->label('Metode HTTP')
                                ->badge()
                                ->color(fn ($state) => match ($state) {
                                    'GET' => 'info',
                                    'POST' => 'success',
                                    'PUT', 'PATCH' => 'warning',
                                    'DELETE' => 'danger',
                                    default => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('url')
                                ->label('URL')
                                ->icon('heroicon-o-link'),

                            Infolists\Components\TextEntry::make('user_agent')
                                ->label('User Agent')
                                ->icon('heroicon-o-device-phone-mobile')
                                ->limit(50)
                                ->tooltip(fn ($record) => $record->user_agent),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Waktu Tercatat')
                                ->dateTime('d M Y, H:i:s')
                                ->icon('heroicon-o-clock'),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Waktu')
                    ->icon('heroicon-o-clock')
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('severity')
                    ->label('Tingkat')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'info' => 'info',
                        'warning' => 'warning',
                        'error', 'critical' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'info' => 'heroicon-o-information-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'error' => 'heroicon-o-x-circle',
                        'critical' => 'heroicon-o-fire',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'info' => 'Info',
                        'warning' => 'Peringatan',
                        'error' => 'Error',
                        'critical' => 'Kritis',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Sumber')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-rectangle-stack')
                    ->searchable(),

                Tables\Columns\TextColumn::make('validation_type')
                    ->label('Tipe')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'duplicate' => 'Duplikat',
                        'stock' => 'Stok',
                        'required' => 'Wajib Diisi',
                        'format' => 'Format',
                        'business_rule' => 'Aturan Bisnis',
                        'minimum_items' => 'Min. Item',
                        'batch_not_found' => 'Batch ?',
                        default => $state,
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->searchable()
                    ->label('Pesan Error')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->wrap(),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Pengguna')
                    ->icon('heroicon-o-user-circle')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_resolved')
                    ->boolean()
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source_type')
                    ->label('Sumber')
                    ->options([
                        'Penjualan' => 'Penjualan',
                        'Pembelian' => 'Pembelian',
                        'TukarTambah' => 'Tukar Tambah',
                    ])
                    ->placeholder('Semua Sumber'),

                Tables\Filters\SelectFilter::make('validation_type')
                    ->label('Tipe Validasi')
                    ->options([
                        'duplicate' => 'Duplikat',
                        'stock' => 'Stok',
                        'required' => 'Wajib Diisi',
                        'format' => 'Format',
                        'business_rule' => 'Aturan Bisnis',
                        'minimum_items' => 'Minimum Item',
                        'batch_not_found' => 'Batch Tidak Ditemukan',
                    ])
                    ->placeholder('Semua Tipe'),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Tingkat')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Peringatan',
                        'error' => 'Error',
                        'critical' => 'Kritis',
                    ])
                    ->placeholder('Semua Tingkat'),

                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah Diselesaikan')
                    ->falseLabel('Belum Diselesaikan'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('created_at', '<=', $data['to']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Dari: '.\Carbon\Carbon::parse($data['from'])->format('d M Y');
                        }
                        if ($data['to'] ?? null) {
                            $indicators['to'] = 'Sampai: '.\Carbon\Carbon::parse($data['to'])->format('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),

                Tables\Actions\Action::make('resolve')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-check-badge')
                    ->modalIconColor('success')
                    ->modalHeading('Tandai Sebagai Selesai')
                    ->modalDescription('Apakah Anda yakin ingin menandai log validasi ini sebagai sudah diselesaikan?')
                    ->modalSubmitActionLabel('Ya, Selesaikan')
                    ->modalCancelActionLabel('Batal')
                    ->visible(fn ($record) => ! $record->is_resolved)
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Catatan Penyelesaian')
                            ->placeholder('Catatan opsional tentang bagaimana masalah ini diselesaikan...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->markAsResolved($data['resolution_notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Log validasi berhasil ditandai selesai')
                            ->icon('heroicon-o-check-circle')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolve')
                        ->label('Tandai Selesai')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Tandai Selesai (Massal)')
                        ->modalDescription('Apakah Anda yakin ingin menandai semua log yang dipilih sebagai selesai?')
                        ->modalSubmitActionLabel('Ya, Selesaikan Semua')
                        ->modalCancelActionLabel('Batal')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (! $record->is_resolved) {
                                    $record->markAsResolved('Diselesaikan secara massal');
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("{$count} log validasi berhasil ditandai selesai")
                                ->icon('heroicon-o-check-circle')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s')
            ->emptyStateHeading('Tidak Ada Log Validasi')
            ->emptyStateDescription('Belum ada log validasi yang tercatat. Log akan otomatis tercatat saat terjadi kesalahan validasi pada transaksi.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidationLogs::route('/'),
            'view' => Pages\ViewValidationLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
