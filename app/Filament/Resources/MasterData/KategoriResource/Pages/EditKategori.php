<?php

namespace App\Filament\Resources\MasterData\KategoriResource\Pages;

use App\Filament\Resources\MasterData\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Filament\Notifications\Notification;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

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
                ->title('Kategori sudah ada')
                ->body('Nama kategori atau slug sudah digunakan. Silakan gunakan nama yang berbeda.')
                ->danger()
                ->send();
            
            $this->halt();
        }
    }
}
