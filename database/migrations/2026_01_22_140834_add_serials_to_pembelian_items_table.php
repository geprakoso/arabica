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
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            $table->json('serials')->nullable()->after('kondisi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_pembelian_item', function (Blueprint $table) {
            $table->dropColumn('serials');
        });
    }
};
