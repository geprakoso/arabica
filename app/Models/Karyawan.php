<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Models\User;
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
}
