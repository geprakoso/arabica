<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        'gudang_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dokumen_karyawan' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Karyawan $karyawan) {
            $karyawan->slug ??= self::generateUniqueSlug($karyawan->nama_karyawan ?? Str::random(8));
        });

        static::updating(function (Karyawan $karyawan) {
            if ($karyawan->isDirty('nama_karyawan') && blank($karyawan->slug)) {
                $karyawan->slug = self::generateUniqueSlug($karyawan->nama_karyawan, $karyawan->id);
            }
        });
    }

    /**
     * Generate a slug and keep it unique by appending a counter when needed.
     */
    public static function generateUniqueSlug(string $namaKaryawan, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($namaKaryawan) ?: Str::random(8);
        $slug = $baseSlug;
        $counter = 1;

        while (
            self::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
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
    public function gajiKaryawans()
    {
        return $this->hasMany(GajiKaryawan::class);
    }

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'id_karyawan');
    }

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_karyawan');
    }

    public function tukarTambah()
    {
        return $this->hasMany(TukarTambah::class, 'id_karyawan');
    }
}
