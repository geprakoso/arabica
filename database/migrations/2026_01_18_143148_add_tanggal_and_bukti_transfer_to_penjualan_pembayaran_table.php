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
        Schema::table('tb_penjualan_pembayaran', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->after('id_penjualan');
            $table->string('bukti_transfer')->nullable()->after('jumlah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_penjualan_pembayaran', function (Blueprint $table) {
            $table->dropColumn(['tanggal', 'bukti_transfer']);
        });
    }
};
