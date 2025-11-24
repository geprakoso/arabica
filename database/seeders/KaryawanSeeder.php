<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = Filament::getDefaultPanel()?->getAuthGuard() ?? config('auth.defaults.guard', 'web');
        $superAdminRoleName = config('filament-shield.super_admin.name', 'Super Admin');

        $superAdminRole = Role::where('name', $superAdminRoleName)
            ->where('guard_name', $guardName)
            ->first();

        if (! $superAdminRole) {
            return;
        }

        $karyawanList = [
            [
                'nama_karyawan' => 'Galih',
                'telepon' => '088238555555',
                'email' => 'galih@example.com',
                'password' => 'password',
            ],
            [
                'nama_karyawan' => 'Huda',
                'telepon' => '080035554646',
                'email' => 'huda@example.com',
                'password' => 'password',
            ],
        ];

        foreach ($karyawanList as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['nama_karyawan'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $user->assignRole($superAdminRole);

            Karyawan::updateOrCreate(
                ['telepon' => $data['telepon']],
                [
                    'nama_karyawan' => $data['nama_karyawan'],
                    'telepon' => $data['telepon'],
                    'user_id' => $user->id,
                    'role_id' => $superAdminRole->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
