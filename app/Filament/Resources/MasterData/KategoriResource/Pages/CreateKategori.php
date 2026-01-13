<?php

namespace App\Filament\Resources\MasterData\KategoriResource\Pages;

use App\Filament\Resources\MasterData\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Filament\Notifications\Notification;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            return static::getModel()::create($data);
        } catch (UniqueConstraintViolationException $e) {
            Notification::make()
                ->title('Kategori sudah ada')
                ->body('Nama kategori atau slug sudah digunakan. Silakan gunakan nama yang berbeda.')
                ->danger()
                ->send();
            
            $this->halt();
        }
    }
}
