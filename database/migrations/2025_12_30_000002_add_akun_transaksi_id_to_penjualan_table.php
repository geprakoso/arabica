<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->foreignId('akun_transaksi_id')
                ->nullable()
                ->after('metode_bayar')
                ->constrained('akun_transaksis')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->dropForeign(['akun_transaksi_id']);
            $table->dropColumn('akun_transaksi_id');
        });
    }
};
