<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Karyawan;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load karyawan data into the form
        $karyawan = $this->record->karyawan;

        if ($karyawan) {
            $data['karyawan'] = [
                'nama_karyawan' => $karyawan->nama_karyawan,
                'slug' => $karyawan->slug,
                'telepon' => $karyawan->telepon,
                'alamat' => $karyawan->alamat,
                'provinsi' => $karyawan->provinsi,
                'kota' => $karyawan->kota,
                'kecamatan' => $karyawan->kecamatan,
                'kelurahan' => $karyawan->kelurahan,
                'dokumen_karyawan' => $karyawan->dokumen_karyawan ?? [],
                'image_url' => $karyawan->image_url,
                'is_active' => $karyawan->is_active,
                'gudang_id' => $karyawan->gudang_id,
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Sync name from karyawan.nama_karyawan if changed
        if (! empty($data['karyawan']['nama_karyawan'])) {
            $data['name'] = $data['karyawan']['nama_karyawan'];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;
        $karyawanData = $data['karyawan'] ?? [];

        if (! empty($karyawanData)) {
            $karyawan = $this->record->karyawan;

            // Generate slug if not set
            if (empty($karyawanData['slug'])) {
                $karyawanData['slug'] = Karyawan::generateUniqueSlug(
                    $karyawanData['nama_karyawan'] ?? Str::random(8),
                    $karyawan?->id
                );
            }

            // Extract image_url from Filament's JSON format if needed
            $imageUrl = $karyawanData['image_url'] ?? null;
            if (is_array($imageUrl)) {
                // Filament stores as ['uuid' => 'path'], extract the path
                $imageUrl = !empty($imageUrl) ? reset($imageUrl) : null;
            }

            $updateData = [
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
            ];

            if ($karyawan) {
                // Update existing Karyawan
                $karyawan->update($updateData);
            } else {
                // Create new Karyawan if doesn't exist
                $this->record->karyawan()->create($updateData);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
