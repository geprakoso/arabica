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
            if (Schema::hasColumn('tb_penjualan', 'dp')) {
                $table->dropColumn('dp');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->decimal('dp', 15, 2)->default(0)->after('kembalian');
        });
    }
};
