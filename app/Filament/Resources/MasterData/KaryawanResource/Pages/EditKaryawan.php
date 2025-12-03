<?php

namespace App\Filament\Resources\MasterData\KaryawanResource\Pages;

use App\Filament\Resources\MasterData\KaryawanResource;
use App\Models\Karyawan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKaryawan extends EditRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record instanceof Karyawan && $this->record->user) {
            $this->record->user->update([
                'name' => $data['nama_karyawan'],
                'email' => $data['login_email'],
            ]);
        }

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        return $data;
    }
}
