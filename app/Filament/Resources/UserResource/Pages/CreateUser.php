<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Karyawan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Sync name from karyawan.nama_karyawan if not set
        if (empty($data['name']) && ! empty($data['karyawan']['nama_karyawan'])) {
            $data['name'] = $data['karyawan']['nama_karyawan'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->data;
        $karyawanData = $data['karyawan'] ?? [];

        if (! empty($karyawanData)) {
            // Generate slug if not set
            if (empty($karyawanData['slug'])) {
                $karyawanData['slug'] = Karyawan::generateUniqueSlug($karyawanData['nama_karyawan'] ?? Str::random(8));
            }

            // Extract image_url from Filament's JSON format if needed
            $imageUrl = $karyawanData['image_url'] ?? null;
            if (is_array($imageUrl)) {
                // Filament stores as ['uuid' => 'path'], extract the path
                $imageUrl = !empty($imageUrl) ? reset($imageUrl) : null;
            }

            // Create the Karyawan record linked to the new User
            $this->record->karyawan()->create([
                'nama_karyawan' => $karyawanData['nama_karyawan'] ?? null,
                'slug' => $karyawanData['slug'],
                'telepon' => $karyawanData['telepon'] ?? null,
                'alamat' => $karyawanData['alamat'] ?? null,
                'provinsi' => $karyawanData['provinsi'] ?? null,
                'kota' => $karyawanData['kota'] ?? null,
                'kecamatan' => $karyawanData['kecamatan'] ?? null,
                'kelurahan' => $karyawanData['kelurahan'] ?? null,
                'dokumen_karyawan' => $karyawanData['dokumen_karyawan'] ?? [],
                'image_url' => $imageUrl,
                'is_active' => $karyawanData['is_active'] ?? true,
                'role_id' => $this->record->roles->first()?->id,
                'gudang_id' => $karyawanData['gudang_id'] ?? null,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
