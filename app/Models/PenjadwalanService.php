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
        'has_crosscheck',
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

    public function crosschecks()
    {
        return $this->belongsToMany(Crosscheck::class, 'penjadwalan_service_crosscheck');
    }

    public function listAplikasis()
    {
        return $this->belongsToMany(ListAplikasi::class, 'penjadwalan_service_list_aplikasi');
    }

    public function listGames()
    {
        return $this->belongsToMany(ListGame::class, 'penjadwalan_service_list_game');
    }

    public function listOs()
    {
        return $this->belongsToMany(ListOs::class, 'penjadwalan_service_list_os');
    }
}
