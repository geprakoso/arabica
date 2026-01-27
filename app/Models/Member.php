<?php

namespace App\Models;

use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanJasa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory;

    protected $table = 'md_members';

    protected $fillable = [
        'kode_member',
        'nama_member',
        'email',
        'no_hp',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'image_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            $member->kode_member ??= self::generateKode($member->nama_member ?? 'MEM', $member->created_at);
        });
    }

    public static function generateKode(string $nama, ?Carbon $date = null): string
    {
        $date = $date ?? now();
        $cleanName = Str::upper(Str::ascii($nama));
        $cleanName = preg_replace('/[^A-Z0-9]/', '', $cleanName) ?? '';
        $prefixName = substr($cleanName, 0, 4);
        $prefixName = str_pad($prefixName, 4, 'X');
        $prefix = $prefixName . '-';

        $number = (int) self::where('kode_member', 'like', $prefix . '%')
            ->selectRaw('MAX(CAST(SUBSTRING(kode_member, ?) AS UNSIGNED)) as max_num', [strlen($prefix) + 1])
            ->value('max_num');

        do {
            $number++;
            $kode = $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        } while (self::where('kode_member', $kode)->exists());

        return $kode;
    }

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class, 'id_member', 'id');
    }

    public function penjualanItems()
    {
        return $this->hasManyThrough(
            PenjualanItem::class,
            Penjualan::class,
            'id_member',
            'id_penjualan',
            'id',
            'id_penjualan'
        );
    }

    public function penjualanJasa()
    {
        return $this->hasManyThrough(
            PenjualanJasa::class,
            Penjualan::class,
            'id_member',
            'id_penjualan',
            'id',
            'id_penjualan'
        );
    }
}
