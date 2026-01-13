<?php

namespace App\Filament\Resources\MasterData\BrandResource\Pages;

use App\Filament\Resources\MasterData\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Filament\Notifications\Notification;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            $record->update($data);
            return $record;
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
