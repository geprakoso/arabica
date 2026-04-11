<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Models\Pembelian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PembelianResource;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Buat')
                ->icon('heroicon-o-plus')
                ->formId('form'),
            $this->getCancelFormAction()
                ->label('Batal')
                ->formId('form')
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pembelian berhasil dibuat. Silakan tambah produk melalui tabel di bawah.';
    }

    protected function handleRecordCreation(array $data): Pembelian
    {
        // Remove no_po from data to let the model's booted() event generate a unique one
        // This ensures the model's generateUniquePO() with soft-delete check is used
        unset($data['no_po']);

        return parent::handleRecordCreation($data);
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Pembelian baru dibuat')
            ->body("No.PO {$this->record->no_po} ditambahkan inventory.")
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(PembelianResource::getUrl('edit', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
    }
}
