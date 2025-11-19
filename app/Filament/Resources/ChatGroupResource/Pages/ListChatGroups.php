<?php

namespace App\Filament\Resources\ChatGroupResource\Pages;

use App\Filament\Resources\ChatGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListChatGroups extends ListRecords
{
    protected static string $resource = ChatGroupResource::class; // Link page to resource definition.

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(), // Provide quick access to create button.
        ];
    }
}
