<?php

namespace App\Models;

use App\Models\User;
use App\Models\Member;
use App\Models\Jasa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenjadwalanService extends Model
{
    use HasFactory;

    protected $table = 'penjadwalan_service';

    protected $fillable = [
        'member_id',
        'nama_perangkat',
        'kelengkapan',
        'keluhan',
        'catatan_teknisi',
        'no_resi',
        'status',
        'technician_id',
        'jasa_id',
        'estimasi_selesai',
    ];

    protected $casts = [
        'estimasi_selesai' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function jasa()
    {
        return $this->belongsTo(Jasa::class);
    }
}
