<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            if (! Schema::hasColumn('tb_pembelian_item', 'qty_masuk')) {
                $table->unsignedInteger('qty_masuk')->nullable()->after('qty');
            }

            if (! Schema::hasColumn('tb_pembelian_item', 'qty_sisa')) {
                $table->unsignedInteger('qty_sisa')->nullable()->after('qty_masuk');
            }
        });

        if (Schema::hasColumn('tb_pembelian_item', 'qty_masuk')) {
            DB::table('tb_pembelian_item')
                ->whereNull('qty_masuk')
                ->update(['qty_masuk' => DB::raw('qty')]);
        }

        if (Schema::hasColumn('tb_pembelian_item', 'qty_sisa')) {
            DB::table('tb_pembelian_item')
                ->whereNull('qty_sisa')
                ->update(['qty_sisa' => DB::raw('qty')]);
        }
    }

    public function down(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            if (Schema::hasColumn('tb_pembelian_item', 'qty_sisa')) {
                $table->dropColumn('qty_sisa');
            }

            if (Schema::hasColumn('tb_pembelian_item', 'qty_masuk')) {
                $table->dropColumn('qty_masuk');
            }
        });
    }
};
