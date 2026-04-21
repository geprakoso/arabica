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
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->string('status_dokumen', 20)->default('final')->after('sumber_transaksi');
            $table->index('status_dokumen', 'idx_penjualan_status_dokumen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table) {
            $table->dropIndex('idx_penjualan_status_dokumen');
            $table->dropColumn('status_dokumen');
        });
    }
};
