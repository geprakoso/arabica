<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListGame extends Model
{
    //
    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(ListGame::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ListGame::class, 'parent_id');
    }
}
