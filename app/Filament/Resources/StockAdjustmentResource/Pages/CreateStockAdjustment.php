<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
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
            ->sendToDatabase(Auth::user());
    }

    
}
