<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function (): string {
            $prefix = 'RO-';

            $latest = self::query()
                ->where('no_ro', 'like', $prefix . '%')
                ->orderBy('no_ro', 'desc')
                ->lockForUpdate()
                ->first();

            $next = 1;

            if ($latest && preg_match('/^' . preg_quote($prefix, '/') . '(\\d+)$/', (string) $latest->no_ro, $matches)) {
                $next = ((int) $matches[1]) + 1;
            }

            return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
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
