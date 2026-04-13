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
            if (! Schema::hasColumn('tb_penjualan', 'status_dokumen')) {
                $table->string('status_dokumen', 20)
                    ->default('final')
                    ->after('status_pembayaran');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            if (Schema::hasColumn('tb_penjualan', 'status_dokumen')) {
                $table->dropColumn('status_dokumen');
            }
        });
    }
};
