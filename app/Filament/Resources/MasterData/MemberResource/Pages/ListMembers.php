<?php

namespace App\Filament\Resources\MasterData\MemberResource\Pages;

use App\Filament\Resources\MasterData\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Member')
                ->icon('heroicon-m-plus'),
        ];
    }
}
