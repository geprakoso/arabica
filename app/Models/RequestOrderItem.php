<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestOrderItem extends Model
{
    use HasFactory;

    protected $table = 'request_order_items';

    protected $fillable = [
        'request_order_id',
        'produk_id',
    ];

    public function requestOrder()
    {
        return $this->belongsTo(RequestOrder::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
