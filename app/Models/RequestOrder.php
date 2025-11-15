<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestOrder extends Model
{
    use HasFactory;

    protected $table = 'request_orders';

    protected $fillable = [
        'no_ro',
        'tanggal',
        'catatan',
        'karyawan_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(RequestOrderItem::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }
}
