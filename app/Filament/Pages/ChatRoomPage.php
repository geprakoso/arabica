<?php

namespace App\Filament\Pages;

use Chatify\Facades\ChatifyMessenger;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class ChatRoomPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left';

    protected static ?string $slug = "chat";
    protected static ?string $navigationLabel = "Chat";
    protected static ?string $title = "Chat";
    protected ?string $heading = '';

    protected static string $view = 'filament.pages.chat-room-page';

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => false)
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(route(config('chatify.routes.prefix')), shouldOpenInNewTab: true),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    protected function getViewData(): array
    {
        $user = auth()->guard('web')->user();

        return [
            'messengerColor' => $user?->messenger_color ?: ChatifyMessenger::getFallbackColor(),
            'darkMode' => ($user?->dark_mode ?? 0) < 1 ? 'light' : 'dark',
        ];
    }
}
