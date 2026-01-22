<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationResource extends BaseResource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifikasi';

    protected static ?string $pluralLabel = 'Notifikasi';


    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'notifications';

    public static function getEloquentQuery(): Builder
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->where('notifiable_id', $user->getAuthIdentifier())
            ->where('notifiable_type', $user::class);
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return null;
        }

        $count = $user->unreadNotifications()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->getStateUsing(fn(DatabaseNotification $record): bool => !!$record->read_at)
                    ->icon(fn(bool $state): string => $state ? 'heroicon-o-envelope-open' : 'heroicon-s-envelope')
                    ->color(fn(bool $state): string => $state ? 'warning' : 'success')
                    ->tooltip(fn(bool $state): string => $state ? 'Sudah dibaca' : 'Belum dibaca'),
                TextColumn::make('data.title')
                    ->label('Pesan')
                    ->weight(fn(DatabaseNotification $record): string => $record->unread() ? 'bold' : 'normal')
                    ->formatStateUsing(fn($state, DatabaseNotification $record): string => $state
                        ?: (string) data_get($record->data, 'title', data_get($record->data, 'subject', class_basename($record->type))))
                    ->description(fn(DatabaseNotification $record): string => Str::limit((string) data_get($record->data, 'body'), 100))
                    ->wrap()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable()
                    ->alignEnd()
                    ->color('gray')
                    ->size('xs'),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Belum dibaca')
                    ->query(fn(Builder $query): Builder => $query->whereNull('read_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->iconButton()
                    ->url(fn(DatabaseNotification $record): ?string => static::getNotificationUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn(DatabaseNotification $record): bool => filled(static::getNotificationUrl($record))),
                Tables\Actions\Action::make('mark_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->iconButton()
                    ->action(fn(DatabaseNotification $record) => $record->markAsRead())
                    ->visible(fn(DatabaseNotification $record): bool => $record->unread()),
                Tables\Actions\Action::make('mark_unread')
                    ->label('Tandai Belum Dibaca')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->iconButton()
                    ->action(fn(DatabaseNotification $record) => $record->markAsUnread())
                    ->visible(fn(DatabaseNotification $record): bool => $record->read()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_read')
                    ->label('Tandai Dibaca')
                    ->icon('heroicon-m-check')
                    ->action(fn(Collection $records) => $records->each->markAsRead())
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('mark_unread')
                    ->label('Tandai Belum Dibaca')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->action(fn(Collection $records) => $records->each->markAsUnread())
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }

    protected static function getNotificationUrl(DatabaseNotification $record): ?string
    {
        $data = $record->data ?? [];

        if (isset($data['actions']) && is_array($data['actions'])) {
            foreach ($data['actions'] as $action) {
                $url = $action['url'] ?? null;
                if (is_string($url) && $url !== '') {
                    return $url;
                }
            }
        }

        $url = $data['url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }
}
