<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanLabaRugi extends Model
{
    protected $table = 'laporan_laba_rugis';
    protected $primaryKey = 'month_key';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
