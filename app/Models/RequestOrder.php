<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    public static function generateRO(): string
    {
        $lastNumber = self::where('no_ro', 'like', 'MD%')
            ->selectRaw('MAX(CAST(SUBSTRING(no_ro, 4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return 'RO-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(RequestOrderItem::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
