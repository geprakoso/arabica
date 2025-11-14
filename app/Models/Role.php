<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    //
    use HasFactory;
    protected $table = 'md_role';
    protected $fillable = [
        'name_role',
        'is_active',
        'kode_role',
        'deskripsi',
    ];
}
