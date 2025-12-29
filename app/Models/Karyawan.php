<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Karyawan extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'md_karyawan';

    protected $fillable = [
        'nama_karyawan',
        'slug',
        'telepon',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'dokumen_karyawan',
        'image_url',
        'user_id',
        'role_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dokumen_karyawan' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function requestOrders()
    {
        return $this->hasMany(RequestOrder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Gallery dokumen karyawan (media library + fallback legacy array).
     *
     * @return array<int, array{url: string, name: string}>
     */
    public function dokumenKaryawanGallery(): array
    {
        $items = $this->getMedia('dokumen_karyawan')
            ->map(fn ($media) => [
                'url' => $media->getUrl(),
                'name' => $media->name ?? 'Dokumen Karyawan',
            ])
            ->values()
            ->all();

        if (! empty($items)) {
            return $items;
        }

        if (! is_array($this->dokumen_karyawan)) {
            return [];
        }

        return collect($this->dokumen_karyawan)
            ->filter(fn ($doc) => is_array($doc) && ! empty($doc['file_path']))
            ->map(fn ($doc) => [
                'url' => Storage::disk('public')->url($doc['file_path']),
                'name' => $doc['jenis_dokumen'] ?? 'Dokumen Karyawan',
            ])
            ->values()
            ->all();
    }
}
