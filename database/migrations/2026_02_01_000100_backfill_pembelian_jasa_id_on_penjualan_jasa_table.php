<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tb_penjualan_jasa')
            ->join('tb_pembelian_item', 'tb_penjualan_jasa.pembelian_item_id', '=', 'tb_pembelian_item.id_pembelian_item')
            ->whereNull('tb_penjualan_jasa.pembelian_jasa_id')
            ->whereNotNull('tb_penjualan_jasa.pembelian_item_id')
            ->select(
                'tb_penjualan_jasa.id_penjualan_jasa',
                'tb_penjualan_jasa.jasa_id',
                'tb_pembelian_item.id_pembelian',
            )
            ->orderBy('tb_penjualan_jasa.id_penjualan_jasa')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $pembelianJasaId = DB::table('tb_pembelian_jasa')
                        ->where('id_pembelian', $row->id_pembelian)
                        ->where('jasa_id', $row->jasa_id)
                        ->orderBy('id_pembelian_jasa')
                        ->value('id_pembelian_jasa');

                    if (! $pembelianJasaId) {
                        continue;
                    }

                    DB::table('tb_penjualan_jasa')
                        ->where('id_penjualan_jasa', $row->id_penjualan_jasa)
                        ->whereNull('pembelian_jasa_id')
                        ->update(['pembelian_jasa_id' => $pembelianJasaId]);
                }
            }, 'id_penjualan_jasa');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left blank to avoid destructive rollback.
    }
};
