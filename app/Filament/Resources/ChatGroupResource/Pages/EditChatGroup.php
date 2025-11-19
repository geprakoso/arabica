<?php

namespace App\Filament\Resources\ChatGroupResource\Pages;

use App\Filament\Resources\ChatGroupResource;
use Filament\Resources\Pages\EditRecord;

class EditChatGroup extends EditRecord
{
    protected static string $resource = ChatGroupResource::class; // Bind edit page to resource.

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Chat group updated'; // Provide confirmation toast to admins.
    }
}
