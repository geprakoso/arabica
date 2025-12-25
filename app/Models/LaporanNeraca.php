<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanNeraca extends Model
{
    protected $table = 'laporan_neracas';
    protected $primaryKey = 'month_key';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
}
