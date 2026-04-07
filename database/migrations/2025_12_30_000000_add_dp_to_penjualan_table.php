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
            $table->decimal('dp', 15, 2)->default(0)->after('kembalian');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan')) {
            return;
        }

        Schema::table('tb_penjualan', function (Blueprint $table): void {
            $table->dropColumn('dp');
        });
    }
};
