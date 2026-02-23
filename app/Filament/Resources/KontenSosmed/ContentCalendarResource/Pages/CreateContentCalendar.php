<?php

namespace App\Filament\Resources\KontenSosmed\ContentCalendarResource\Pages;

use App\Filament\Resources\KontenSosmed\ContentCalendarResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentCalendar extends CreateRecord
{
    protected static string $resource = ContentCalendarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
