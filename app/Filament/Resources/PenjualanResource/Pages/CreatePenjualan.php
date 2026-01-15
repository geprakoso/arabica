<?php

namespace App\Filament\Resources\PenjualanResource\Pages;

use App\Filament\Resources\PenjualanResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

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
        $user = Auth::user();
        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Penjualan baru dibuat')
            ->body("No. Nota {$this->record->no_nota} siap ditambahkan produk.")
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('Lihat')
                    ->url(PenjualanResource::getUrl('edit', ['record' => $this->record])),
            ])
            ->sendToDatabase($user);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            return parent::handleRecordCreation($data);
        });
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
