<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;

    protected $table = 'md_members';

    protected $fillable = [
        'nama_member',
        'email',
        'no_hp',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'image_url',
    ];
}
