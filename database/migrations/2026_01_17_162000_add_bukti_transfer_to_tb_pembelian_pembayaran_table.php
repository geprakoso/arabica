<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pembelian_pembayaran', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_pembelian_pembayaran', 'bukti_transfer')) {
                $table->string('bukti_transfer')->nullable()->after('jumlah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_pembelian_pembayaran', function (Blueprint $table) {
            $table->dropColumn('bukti_transfer');
        });
    }
};
