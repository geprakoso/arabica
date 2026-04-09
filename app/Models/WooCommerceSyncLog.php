<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WooCommerceSyncLog extends Model
{
    protected $table = 'woocommerce_sync_logs';

    protected $fillable = [
        'produk_id',
        'woo_product_id',
        'action',
        'request_payload',
        'response_payload',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
