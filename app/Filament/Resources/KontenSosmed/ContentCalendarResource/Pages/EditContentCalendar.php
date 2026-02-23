<?php

namespace App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

use App\Filament\Resources\KontenSosmed\ContentCalendarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentCalendar extends EditRecord
{
    protected static string $resource = ContentCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
