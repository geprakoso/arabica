<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListAplikasi extends Model
{
    //
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(ListAplikasi::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ListAplikasi::class, 'parent_id');
    }
}
