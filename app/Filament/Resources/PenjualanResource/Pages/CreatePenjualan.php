<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePenjualan extends CreateRecord
{
    protected static string $resource = PenjualanResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return PenjualanResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penjualan berhasil dibuat. Silakan tambah produk melalui tabel di bawah.';
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Penjualan baru dibuat')
            ->body("No. Nota {$this->record->no_nota} siap ditambahkan produk.")
            ->icon('heroicon-o-check-circle')
            ->sendToDatabase($user);
    }
}
