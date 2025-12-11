<?php

namespace App\Filament\Resources\MasterData\SupplierResource\Pages;

use App\Filament\Resources\MasterData\SupplierResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function afterCreate(): void
    {
        $supplier = $this->record;
        $recipients = User::all();

        // Kirim notifikasi ke semua user (disimpan di database)
        Notification::make()
            ->title('Supplier Baru')
            ->body("Supplier **{$supplier->nama_supplier}** siap untuk transaksi.")
            ->info()
            ->sendToDatabase($recipients);
    }
}
