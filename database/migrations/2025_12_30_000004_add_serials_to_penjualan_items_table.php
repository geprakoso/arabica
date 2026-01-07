<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tb_penjualan_item')) {
            return;
        }

        Schema::table('tb_penjualan_item', function (Blueprint $table): void {
            $table->json('serials')->nullable()->after('kondisi');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tb_penjualan_item')) {
            return;
        }

        Schema::table('tb_penjualan_item', function (Blueprint $table): void {
            $table->dropColumn('serials');
        });
    }
};
