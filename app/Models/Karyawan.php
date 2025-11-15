<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use App\Models\User;

class Karyawan extends Model
{
    protected $table = 'md_karyawan';

    protected $fillable = [
        'nama_karyawan',
        'telepon',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'image_url',
        'user_id',
        'role_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
