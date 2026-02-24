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
            $this->getSaveFormAction()
                ->label('Simpan')
                ->icon('heroicon-m-check')
                ->color('primary')
                ->submit(null)
                ->action('save'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->icon('heroicon-m-x-mark')
                ->color('danger'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
