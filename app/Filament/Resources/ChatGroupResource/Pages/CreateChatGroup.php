<?php

namespace App\Filament\Resources\ChatGroupResource\Pages;

use App\Filament\Resources\ChatGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChatGroup extends CreateRecord
{
    protected static string $resource = ChatGroupResource::class; // Hook page into resource config.

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Chat group created'; // Friendly toast message for admins.
    }
}
