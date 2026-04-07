<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilePerusahaan extends Model
{
    use HasFactory;

    protected $table = 'profile_perusahaan';

    protected $fillable = [
        // Kolom tabel
        'nama_perusahaan',
        'alamat_perusahaan',
        'email',
        'telepon',
        'npwp',
        'tanggal_pkp',
        'nama_pkp',
        'alamat_pkp',
        'telepon_pkp',
        'logo_link',
        'lat_perusahaan',
        'long_perusahaan',
        // Alias yang dipakai di form Filament
        'name',
        'address',
        'phone',
        'logo',
    ];

    // Tambah alias agar attributesToArray() menyertakan field yang dipakai form
    protected $appends = [
        'name',
        'address',
        'phone',
        'logo',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nama_perusahaan'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['nama_perusahaan'] = $value;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['alamat_perusahaan'] ?? null;
    }

    public function setAddressAttribute(?string $value): void
    {
        $this->attributes['alamat_perusahaan'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['telepon'] ?? null;
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['telepon'] = $value;
    }

    public function getLogoAttribute(): ?string
    {
        return $this->attributes['logo_link'] ?? null;
    }

    public function setLogoAttribute(?string $value): void
    {
        $this->attributes['logo_link'] = $value;
    }
}
