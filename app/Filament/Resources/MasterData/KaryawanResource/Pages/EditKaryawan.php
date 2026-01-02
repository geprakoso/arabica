<?php

namespace App\Filament\Resources\MasterData\KaryawanResource\Pages;

use App\Filament\Resources\MasterData\KaryawanResource;
use App\Models\Karyawan;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['login_email'] = $this->record?->user?->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record instanceof Karyawan && $this->record->user) {
            $userData = [
                'name' => $data['nama_karyawan'],
            ];

            if (array_key_exists('login_email', $data)) {
                $userData['email'] = $data['login_email'];
            }

            if (isset($data['password'])) {
                $userData['password'] = $data['password'];
            }

            $this->record->user->update($userData);

            if (isset($data['role_id'])) {
                $role = Role::find($data['role_id']);
                $this->record->user->syncRoles($role ? [$role] : []);
            }
        }

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        return $data;
    }
}
