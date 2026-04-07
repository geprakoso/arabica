<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table): void {
            if (! Schema::hasColumn('tb_penjualan', 'sumber_transaksi')) {
                $table->string('sumber_transaksi', 20)
                    ->default('manual')
                    ->after('gudang_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_penjualan', function (Blueprint $table): void {
            if (Schema::hasColumn('tb_penjualan', 'sumber_transaksi')) {
                $table->dropColumn('sumber_transaksi');
            }
        });
    }
};
