<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $kasirRole = Role::firstOrCreate([
            'name' => 'kasir',
            'guard_name' => $guardName,
        ]);

        $kasirRole->syncPermissions([
            // POS Penjualan & Aktivitas
            'view_any_pos::sale',
            'view_pos::sale',
            'create_pos::sale',
            'update_pos::sale',
            'delete_pos::sale',
            'delete_any_pos::sale',
            'restore_pos::sale',
            'restore_any_pos::sale',
            'force_delete_pos::sale',
            'force_delete_any_pos::sale',
            'replicate_pos::sale',
            'reorder_pos::sale',
            // Inventory (Produk)
            'view_any_master::data::produk',
            'view_master::data::produk',
            // Stock & Inventory landing page
            'page_StockInventory',
        ]);

        $rolesByKey = [
            'super_admin' => $superAdminRole,
            'kasir' => $kasirRole,
        ];

        $karyawanList = [
            [
                'nama_karyawan' => 'Galih',
                'telepon' => '088238555555',
                'email' => 'galih@example.com',
                'password' => 'password',
                'alamat' => 'Jl. Mawar No. 1',
                'kota' => 'Bandung',
            ],
            [
                'nama_karyawan' => 'Huda',
                'telepon' => '080035554646',
                'email' => 'huda@example.com',
                'password' => 'password',
                'alamat' => 'Jl. Melati No. 2',
                'kota' => 'Jakarta',
            ],
            [
                'nama_karyawan' => 'Kasir POS',
                'telepon' => '081200001111',
                'email' => 'kasir@example.com',
                'password' => 'password',
                'alamat' => 'Jl. Cendana No. 3',
                'kota' => 'Bandung',
                'role' => 'kasir',
            ],
        ];

        foreach ($karyawanList as $data) {
            $roleKey = $data['role'] ?? 'super_admin';
            $role = $rolesByKey[$roleKey] ?? $superAdminRole;
            $slug = Str::slug($data['nama_karyawan']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['nama_karyawan'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $user->assignRole($role);

            Karyawan::updateOrCreate(
                ['telepon' => $data['telepon']],
                [
                    'nama_karyawan' => $data['nama_karyawan'],
                    'slug' => $slug,
                    'telepon' => $data['telepon'],
                    'alamat' => $data['alamat'] ?? null,
                    'kota' => $data['kota'] ?? null,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
