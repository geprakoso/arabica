<?php

namespace App\Filament\Pages;

use Chatify\Facades\ChatifyMessenger;
use Filament\Pages\Page;

class ChatRoomPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-oval-left';

    protected static ?string $slug = "chat";
    protected static ?string $navigationLabel = "Chat";
    protected static ?string $title = "Chat";

    protected static string $view = 'filament.pages.chat-room-page';

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'messengerColor' => $user?->messenger_color ?: ChatifyMessenger::getFallbackColor(),
            'darkMode' => ($user?->dark_mode ?? 0) < 1 ? 'light' : 'dark',
        ];
    }
}
