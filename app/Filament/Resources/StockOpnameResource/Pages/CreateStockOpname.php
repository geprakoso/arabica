<?php

namespace App\Filament\Resources\StockOpnameResource\Pages;

use Mockery\Matcher\Not;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\StockOpnameResource;

class CreateStockOpname extends CreateRecord
{
    protected static string $resource = StockOpnameResource::class;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Buat Stock Opname')
            ->icon('heroicon-o-plus');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->hidden()
        ;
    }




    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Stock Opname berhasil dibuat. Silakan tambah produk melalui tabel di bawah.')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(StockOpnameResource::getUrl('edit', ['record' => $this->record])),
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
