<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crosscheck extends Model
{
    //
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Crosscheck::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Crosscheck::class, 'parent_id');
    }
}
