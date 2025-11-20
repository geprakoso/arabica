<?php

namespace App\Filament\Resources\PembelianResource\Pages;
use Filament\Notifications\Notification;
use App\Filament\Resources\PembelianResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembelian berhasil dibuat. Silakan tambah produk melalui tabel di bawah.';
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Pembelian baru dibuat')
            ->body("No.PO {$this->record->no_po} ditambahkan inventory.")
            ->icon('heroicon-o-check-circle')
            ->sendToDatabase($user);
    }
}


