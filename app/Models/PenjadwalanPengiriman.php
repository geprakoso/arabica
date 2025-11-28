<?php

namespace App\Models;

use App\Models\User;
use App\Models\Member;
use App\Models\Penjualan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PenjadwalanPengiriman extends Model
{
    use HasFactory;

    // Sesuaikan dengan nama tabel di migrasi (saat ini: penjadwalan_pengirimen)
    protected $table = 'penjadwalan_pengiriman';

    protected $fillable = [
        'penjualan_id',
        'member_id',
        'no_resi',
        'penerima_no_hp',
        'alamat',      // jika migrasi masih 'alamat', samakan nama kolomnya
        'catatan',      // jika migrasi masih 'catatan', samakan nama kolomnya
        'karyawan_id',          // kolom driver/kurir yang mengacu ke users
        'status',
        'tanggal_penerimaan',
        'bukti_foto',
        'penerima_nama_asli',
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'penjualan_id', 'id_penjualan');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }
}
