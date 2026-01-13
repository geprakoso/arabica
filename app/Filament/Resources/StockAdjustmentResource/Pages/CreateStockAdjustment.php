<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StockAdjustmentResource;

class CreateStockAdjustment extends CreateRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return  Notification::make()
            ->title('Stock Adjustment berhasil dibuat. Silakan tambah produk melalui tabel di bawah.')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(StockAdjustmentResource::getUrl('edit', ['record' => $this->record])),
            ])
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
