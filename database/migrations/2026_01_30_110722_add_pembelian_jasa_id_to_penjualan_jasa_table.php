<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_penjualan_jasa', function (Blueprint $table) {
            $table->unsignedBigInteger('pembelian_item_id')->nullable()->after('jasa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan_jasa', function (Blueprint $table) {
            $table->dropColumn('pembelian_item_id');
        });
    }
};
