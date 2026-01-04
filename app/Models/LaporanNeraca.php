<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\InputTransaksiToko;


class LaporanNeraca extends Model
{
    protected $table = 'laporan_neracas';
    protected $primaryKey = 'month_key';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    public function newQueryWithoutScopes(): Builder
    {
        return $this->baseQuery();
    }

    public function newQueryForRestoration($ids): Builder
    {
        return $this->baseQuery()->whereKey($ids);
    }

    protected function baseQuery(): Builder
    {
        $transaksiTable = (new InputTransaksiToko())->getTable();

        $monthsSub = InputTransaksiToko::query()
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m-01') as month_start")
            ->selectRaw("DATE_FORMAT({$transaksiTable}.tanggal_transaksi, '%Y-%m') as month_key")
            ->groupBy('month_start', 'month_key');

        return parent::newQueryWithoutScopes()->fromSub($monthsSub, $this->getTable());
    }
}
