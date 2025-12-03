<?php

namespace App\Filament\Resources\MasterData\KaryawanResource\Pages;

use App\Filament\Resources\MasterData\KaryawanResource;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Filament\Resources\Pages\CreateRecord;

class CreateKaryawan extends CreateRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['nama_karyawan']);

        $user = User::create([
            'name' => $data['nama_karyawan'],
            'email' => $data['login_email'],
            'password' => $data['password'],
        ]);

        $role = Role::find($data['role_id']);
        if ($role) {
            $user->assignRole($role);
        }

        $data['user_id'] = $user->id;

        unset($data['login_email'], $data['password'], $data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        //
    }
}
