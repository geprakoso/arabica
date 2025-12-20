<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use Mockery\Matcher\Not;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StockOpnameResource;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Stock Opname berhasil dibuat. Silakan tambah produk melalui tabel di bawah.')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->sendToDatabase(Auth::user());
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()->formId('form'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : []),
            $this->getCancelFormAction(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

}
