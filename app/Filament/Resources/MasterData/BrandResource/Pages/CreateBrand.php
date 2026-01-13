<?php

namespace App\Filament\Resources\MasterData\BrandResource\Pages;

use App\Filament\Resources\MasterData\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Filament\Notifications\Notification;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function afterCreate(): void
    {
        session()->flash('success', 'Brand berhasil dibuat.');
    }

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
                ->title('Brand sudah ada')
                ->body('Nama brand atau slug sudah digunakan. Silakan gunakan nama yang berbeda.')
                ->danger()
                ->send();
            
            $this->halt();
        }
    }
}
