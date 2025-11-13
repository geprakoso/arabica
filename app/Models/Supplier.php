<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    //
    use HasFactory;

    protected $table = 'md_suppliers';

    protected $fillable = [
        'nama_supplier',
        'email',
        'no_hp',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(SupplierAgent::class);
    }
}
